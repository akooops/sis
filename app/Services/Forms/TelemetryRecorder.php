<?php

namespace App\Services\Forms;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The other end of resources/js/lib/forms/telemetry.js.
 *
 * A beacon is a FULL SNAPSHOT, so this is a straight overwrite of the draft's
 * measured columns — no accumulation, no sequence number, no merge. That is what
 * makes a duplicate beacon (pagehide and visibilitychange both fire) and an
 * out-of-order one harmless, and it is the only reason the client can afford to
 * be as careless as it is about sending.
 *
 * NOTHING IN THE PAYLOAD IS TRUSTED. Every number is clamped to its column,
 * every page and field id is checked against the form it claims to belong to,
 * and the arrays are capped. The endpoint is CSRF-excepted, so the submission
 * token is the only thing that got the caller this far.
 *
 * COUNTS ONLY. There is no field on the wire that could carry an answer, and
 * this writes none: a draft has `data` null until the visitor actually submits.
 */
class TelemetryRecorder
{
    /** Field records read from one payload. A form larger than this loses the tail. */
    protected const MAX_FIELDS = 200;

    /** Steps kept on the row. The funnel needs the shape, not every hop. */
    protected const MAX_STEPS = 40;

    public function __construct(protected SubmissionContext $context, protected SubmissionGuard $guard) {}

    /**
     * Write one snapshot.
     *
     * Returns null when nothing was written — no interaction to report, the
     * address has opened too many drafts this hour, or the row for this session
     * is already finished. A beacon never reports which: it is fire-and-forget
     * by construction and the browser is not listening.
     *
     * @param  array<string, mixed>  $payload  the decoded beacon
     * @param  array<string, mixed>  $token    the decrypted SubmissionToken payload
     */
    public function record(Request $request, Form $form, array $payload, array $token): ?FormSubmission
    {
        $records = $this->records($payload);

        if (! $this->interacted($form, $payload, $records)) {
            return null;
        }

        $ip = $request->ip();
        $ipHash = $this->guard->hashIp($ip);
        $client = is_array($payload['client'] ?? null) ? $payload['client'] : [];

        $submission = FormSubmission::query()
            ->where('form_id', $form->id)
            ->where('session_id', $token['sid'])
            ->first();

        // A finished row is the record. A late beacon from the page that was
        // still open when the submit landed must not walk over it.
        if ($submission && $submission->status !== 'started') {
            return null;
        }

        if (! $submission && $this->overDraftLimit($form, $ipHash)) {
            return null;
        }

        $attributes = array_merge(
            $this->context->all($request, $this->clientBlock($payload, $client)),
            $this->measured($form, $payload, $records),
            [
                'duration_seconds' => SubmissionToken::elapsed($token),
                'ip_hash' => $ipHash,
                'ip_address' => $form->is_ip_stored ? $ip : null,
                'fingerprint' => $this->guard->fingerprint($request, $client),
            ],
        );

        if ($submission) {
            /*
             * Re-read under a lock before writing. The last beacon a page sends
             * races the submit it belongs to: without this, a snapshot that read
             * `started` a millisecond before the submit committed would write
             * its measurements — and its abandoned-field marker — over a row
             * that is now the completed record.
             */
            $claimed = DB::transaction(function () use ($submission, $attributes) {
                $fresh = FormSubmission::whereKey($submission->id)->lockForUpdate()->first();

                if (! $fresh || $fresh->status !== 'started') {
                    return null;
                }

                $fresh->forceFill($attributes)->save();

                return $fresh;
            });

            if ($claimed === null) {
                return null;
            }

            $submission = $claimed;
        } else {
            $create = array_merge($attributes, [
                'form_id' => $form->id,
                'status' => 'started',
                'session_id' => $token['sid'],
                // Derived from the token rather than from now(): the row is born
                // on the first interaction, which can be minutes after the page
                // was actually rendered.
                'loaded_at' => now()->subSeconds(SubmissionToken::elapsed($token)),
            ]);

            /*
             * Two forced beacons can leave microseconds apart and be handled in
             * parallel: both SELECT nothing above, and both would insert — one
             * visitor counted twice in every funnel number. The unique index on
             * (form_id, session_id) makes the loser fail rather than duplicate,
             * and here it simply adopts the row the winner created.
             *
             * The client also collapses same-tick beacons, but that is a
             * courtesy; this is the guarantee.
             */
            try {
                $submission = FormSubmission::create($create);
            } catch (UniqueConstraintViolationException) {
                $submission = FormSubmission::query()
                    ->where('form_id', $form->id)
                    ->where('session_id', $token['sid'])
                    ->first();

                // Lost the race AND the winner already finished: its row is the
                // record, so this late beacon has nothing left to say.
                if (! $submission || $submission->status !== 'started') {
                    return null;
                }
            }
        }

        $this->writeFieldEvents($form, $submission, $records, $this->string($payload['open_field'] ?? null, 255));

        return $submission;
    }

    /**
     * A submission that reached a terminal state is no longer abandoned
     * anywhere. Called from the submit pipeline, because the draft it upgrades
     * was very probably carrying the last field the visitor had open.
     */
    public function clearAbandonment(FormSubmission $submission): void
    {
        DB::table('form_submission_field_events')
            ->where('form_submission_id', $submission->id)
            ->where('is_abandoned', true)
            ->update(['is_abandoned' => false, 'updated_at' => now()]);
    }

    /* ------------------------------------------------------------------ */

    /**
     * THE GATE: did a human actually do something?
     *
     * The client refuses to beacon before a real interaction; this is the server
     * saying the same thing, because a crawler or a script can POST whatever it
     * likes and a row per page view is exactly what this endpoint must never
     * become.
     *
     * It used to pass anything where `is_array($payload['steps'])` held — and
     * the client sends `steps: []` on every single beacon, including a
     * single-page form that has no steps at all — so the gate blocked nothing
     * and was decoration. Evidence now has to BE evidence, and each of these
     * three is something only somebody in front of THIS form can produce:
     *   - a field record whose key is a field of this form,
     *   - a submit attempt,
     *   - a page hop between pages of this form.
     * An empty or invented payload satisfies none of them.
     *
     * @param  array<int, array<string, mixed>>  $records
     */
    protected function interacted(Form $form, array $payload, array $records): bool
    {
        $fieldIds = $form->fields->pluck('id', 'key');

        foreach ($records as $record) {
            if ($fieldIds->has($record['key'])) {
                return true;
            }
        }

        if ((int) ($payload['submit_attempts'] ?? 0) >= 1) {
            return true;
        }

        // Null unless at least one hop names a page this form owns — the same
        // filtering measured() writes to the column.
        return $this->steps($payload, $form->pages->pluck('id')->all()) !== null;
    }

    /**
     * Drafts this address has opened for THIS FORM in the last hour.
     *
     * Checked BEFORE the row is created, and it counts abandoned rows too — a
     * sweep that ran mid-hour must not hand the flooder a fresh allowance.
     *
     * SCOPED PER FORM, and that is the point of the form_id clause. An ip_hash
     * is not one visitor: an office, a school or a mobile carrier NATs hundreds
     * of people behind one address, and behind a CDN with TRUSTED_PROXIES unset
     * every visitor on the site hashes to the same value. Counted across all
     * forms, one busy form burned the allowance and every other form on the site
     * stopped recording drafts — a site-wide outage of the funnel caused by
     * normal traffic. Per form, a flooder can still only fill the table they are
     * actually hitting. The (form_id, ip_hash) index serves this.
     */
    protected function overDraftLimit(Form $form, string $ipHash): bool
    {
        $limit = (int) config('forms.submissions.max_drafts_per_hour', 20);

        if ($limit < 1) {
            return false;
        }

        return FormSubmission::query()
            ->where('form_id', $form->id)
            ->where('ip_hash', $ipHash)
            ->where('created_at', '>=', now()->subHour())
            ->whereIn('status', ['started', 'abandoned'])
            ->count() >= $limit;
    }

    /**
     * The measured columns — everything the browser counted, clamped.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array<string, mixed>
     */
    protected function measured(Form $form, array $payload, array $records): array
    {
        $pages = $form->pages->pluck('id')->all();
        $fieldIds = $form->fields->pluck('id', 'key');

        $openKey = $this->string($payload['open_field'] ?? null, 255);
        $openKey = $openKey !== null && $fieldIds->has($openKey) ? $openKey : null;

        return [
            'ttfi_ms' => $this->uint($payload['ttfi_ms'] ?? null),
            'fill_ms' => $this->uint($payload['fill_ms'] ?? null),
            'time_on_page_ms' => $this->uint($payload['time_on_page_ms'] ?? null),

            'click_count' => $this->uint($payload['click_count'] ?? null) ?? 0,
            'paste_count' => $this->uint($payload['paste_count'] ?? null) ?? 0,
            'back_navigations' => $this->uint($payload['back_navigations'] ?? null) ?? 0,
            'submit_attempts' => $this->uint($payload['submit_attempts'] ?? null) ?? 0,
            'scroll_depth_percent' => $this->percent($payload['scroll_depth_percent'] ?? null),
            'pages_completed' => min(65535, $this->uint($payload['pages_completed'] ?? null) ?? 0),

            /*
             * Derived, not sent: the order IS the per-field `o`, and posting it
             * twice would be two things to keep agreeing.
             *
             * Filtered to keys that are actually on this form, for the same
             * reason writeFieldEvents() drops them — a caller can name anything,
             * and a column full of invented keys is worse than a short one.
             */
            'focus_order' => array_values(array_filter(
                array_column($records, 'key'),
                fn ($key) => $fieldIds->has($key),
            )) ?: null,
            'steps' => $this->steps($payload, $pages),
            'last_form_page_id' => $this->oneOf($payload['last_page_id'] ?? null, $pages),

            'abandoned_field_key' => $openKey,
            'abandoned_form_field_id' => $openKey ? $fieldIds->get($openKey) : null,
        ];
    }

    /**
     * The per-field records, in focus order.
     *
     * Short wire keys, mirrored from the client — see the legend at the top of
     * resources/js/lib/forms/telemetry.js. They are short because a snapshot
     * repeats all of them every few seconds.
     *
     * ONE RECORD PER KEY, FIRST OCCURRENCE WINS. The client builds these from a
     * Map so its own payloads are already unique, but nothing on the wire is
     * trusted here: a hand-rolled POST, or two keys long enough to truncate to
     * the same 255 characters, otherwise produces two records for one key, and
     * writeFieldEvents() then hands the upsert two rows carrying the same
     * (submission, field) pair. MySQL does not error on that — it applies them
     * in order, so the LAST one silently wins and the reported numbers depend on
     * array order — but the duplicate also lands twice in the derived
     * `focus_order` column, and on Postgres the same statement is a hard error.
     * Collapsing here costs nothing and removes all three.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function records(array $payload): array
    {
        $fields = $payload['fields'] ?? null;

        if (! is_array($fields)) {
            return [];
        }

        /** @var array<string, array<string, mixed>> keyed by field key, to dedupe */
        $out = [];

        foreach (array_slice($fields, 0, self::MAX_FIELDS) as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $key = $this->string($entry['k'] ?? null, 255);

            if ($key === null || isset($out[$key])) {
                continue;
            }

            $out[$key] = [
                'key' => $key,
                'focus_order' => min(65535, $this->uint($entry['o'] ?? null) ?? (count($out) + 1)),
                'focus_ms' => $this->uint($entry['f'] ?? null) ?? 0,
                'revisits' => min(65535, $this->uint($entry['r'] ?? null) ?? 0),
                'keystrokes' => $this->uint($entry['ks'] ?? null) ?? 0,
                'deletions' => $this->uint($entry['del'] ?? null) ?? 0,
                'final_length' => $this->uint($entry['len'] ?? null) ?? 0,
                'paste_count' => min(65535, $this->uint($entry['p'] ?? null) ?? 0),
            ];
        }

        $out = array_values($out);

        usort($out, fn ($a, $b) => $a['focus_order'] <=> $b['focus_order']);

        return $out;
    }

    /**
     * Replace this submission's field events with the snapshot.
     *
     * ONE upsert, not one write per field: a beacon arrives every few seconds
     * and a per-field round trip would make the endpoint the most expensive
     * thing on the public site.
     *
     * A key that does not resolve to a field on this form is DROPPED rather
     * than written with a null form_field_id — the unique index is
     * (submission, field) and MySQL treats every NULL as distinct, so those
     * rows would multiply on every single beacon.
     *
     * Deliberately DB::table: this model is not audited (see its docblock) and
     * Model::upsert would neither generate the ULID nor buy anything here.
     *
     * @param  array<int, array<string, mixed>>  $records
     */
    protected function writeFieldEvents(Form $form, FormSubmission $submission, array $records, ?string $openKey): void
    {
        $fieldIds = $form->fields->pluck('id', 'key');
        $now = now();
        $rows = [];

        foreach ($records as $record) {
            $fieldId = $fieldIds->get($record['key']);

            if (! $fieldId) {
                continue;
            }

            $rows[] = [
                'id' => (string) Str::ulid(),
                'form_submission_id' => $submission->id,
                'form_field_id' => $fieldId,
                'field_key' => Str::limit($record['key'], 255, ''),
                'focus_order' => $record['focus_order'],
                'focus_ms' => $record['focus_ms'],
                'revisits' => $record['revisits'],
                'keystrokes' => $record['keystrokes'],
                'deletions' => $record['deletions'],
                'final_length' => $record['final_length'],
                'paste_count' => $record['paste_count'],
                // 0 for every other field, so a visitor who came back and moved
                // on does not leave two fields both flagged as the one that lost
                // them. The abandonment sweep reads whichever is true at the end.
                'is_abandoned' => $record['key'] === $openKey,
                'error_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows === []) {
            return;
        }

        DB::table('form_submission_field_events')->upsert(
            $rows,
            ['form_submission_id', 'form_field_id'],
            ['field_key', 'focus_order', 'focus_ms', 'revisits', 'keystrokes', 'deletions',
                'final_length', 'paste_count', 'is_abandoned', 'updated_at'],
        );
    }

    /**
     * The page hops, dropping any id that is not a page of this form.
     *
     * @param  array<int, string>  $pages
     * @return array<int, array<string, mixed>>|null
     */
    protected function steps(array $payload, array $pages): ?array
    {
        $steps = $payload['steps'] ?? null;

        if (! is_array($steps)) {
            return null;
        }

        $out = [];

        foreach (array_slice($steps, 0, self::MAX_STEPS) as $step) {
            if (! is_array($step)) {
                continue;
            }

            $page = $this->oneOf($step['p'] ?? null, $pages);

            if ($page === null) {
                continue;
            }

            $out[] = [
                'page' => $page,
                'at' => $this->uint($step['t'] ?? null) ?? 0,
                'direction' => ($step['d'] ?? 1) ? 'forward' : 'back',
            ];
        }

        return $out ?: null;
    }

    /**
     * The passive signals, merged so the technical columns and the fingerprint
     * read the same values whichever path wrote the row.
     *
     * @param  array<string, mixed>  $client
     * @return array<string, mixed>
     */
    protected function clientBlock(array $payload, array $client): array
    {
        foreach (['timezone', 'timezone_offset_minutes', 'screen_w', 'screen_h', 'viewport_w', 'viewport_h',
            'browser_language', 'connection', 'landing_url', 'referrer'] as $key) {
            if (array_key_exists($key, $payload)) {
                $client[$key] = $payload[$key];
            }
        }

        return $client;
    }

    /** @param array<int, string> $allowed */
    protected function oneOf(mixed $value, array $allowed): ?string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : null;
    }

    /** An unsigned integer column: null for anything that is not one. */
    protected function uint(mixed $value, int $max = 4294967295): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $number = (int) $value;

        return $number < 0 ? 0 : min($number, $max);
    }

    protected function percent(mixed $value): ?int
    {
        $number = $this->uint($value);

        return $number === null ? null : min(100, $number);
    }

    protected function string(mixed $value, int $max): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Str::limit($value, $max, '');
    }
}
