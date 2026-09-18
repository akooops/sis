<?php

namespace App\Services\Forms;

use App\Models\Form;
use App\Models\FormField;
use App\Models\Language;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Everything the analytics dashboard shows, for ONE form.
 *
 * EVERY NUMBER HERE IS COMPUTED BY MySQL. Nothing loads a submission model and
 * nothing iterates a result set of rows — each method issues one grouped query
 * and PHP only ever touches what came back already aggregated (a handful of
 * buckets, one row per field, one row per device). A form with a hundred
 * thousand submissions costs the same page load as one with ten.
 *
 * The range filter is on `created_at`, NOT `submitted_at`. A draft and an
 * abandoned visit have no submitted_at at all, so ranging on it would silently
 * drop exactly the rows the funnel exists to count. created_at is when the
 * visit happened, for every status.
 *
 * The status filter narrows the POPULATION, not just one chart — so
 * `status=completed` legitimately reads 100% completion. That is consistent
 * rather than wrong: the tiles describe whatever set was asked for.
 *
 * Dates are bucketed by MySQL out of the stored value, which Laravel wrote in
 * the app timezone (config('app.timezone')) — so a "day" here is the same day
 * the submissions list shows. A per-admin timezone would have to become a
 * parameter to the bucket expression, never a PHP-side re-grouping.
 */
class SubmissionAnalytics
{
    /** Rows kept per top-N breakdown (referrers, UTM sources). */
    protected int $topN = 10;

    /**
     * A page hop longer than this is a tab someone left open over lunch, not a
     * step. Excluded from the per-step average rather than allowed to move it.
     */
    protected int $maxStepMs = 1_800_000;

    /** Buckets a timeline may return, so an all-time range cannot explode. */
    protected int $maxBuckets = 400;

    /**
     * @param  array{from: ?Carbon, to: ?Carbon, status: ?string}  $filters
     * @return array<string, mixed>
     */
    public function for(Form $form, array $filters): array
    {
        $totals = $this->totals($form, $filters);
        $fields = $this->fields($form, $filters, (int) $totals['starts']);

        return [
            'form' => [
                'id' => $form->id,
                'name' => $form->name,
                'slug' => $form->slug,
                'status' => (string) $form->status,
                'submissions_count' => (int) $form->submissions_count,
                'min_submit_seconds' => (int) ($form->min_submit_seconds ?? config('forms.spam.min_seconds', 5)),
                'is_spam_filtered' => (bool) $form->is_spam_filtered,
            ],
            'range' => [
                'from' => $filters['from']?->toIso8601String(),
                'to' => $filters['to']?->toIso8601String(),
                'status' => $filters['status'],
                'first_at' => $totals['first_at'],
                'last_at' => $totals['last_at'],
            ],
            'totals' => $totals,
            'timeline' => $this->timeline($form, $filters, $totals),
            'fields' => $fields,
            'top_abandoned_field' => $this->topAbandoned($fields),
            'devices' => $this->devices($form, $filters),
            'referrers' => $this->referrers($form, $filters),
            'utm_sources' => $this->utmSources($form, $filters),
            'steps' => $this->steps($form, $filters),
            'honeypot' => $this->honeypot($form, $filters),
        ];
    }

    /* ------------------------------------------------------------------ */

    /**
     * The filtered population, aliased `s` so every query in this class reads
     * the same way and the joins below have something to hang off.
     *
     * @param  array{from: ?Carbon, to: ?Carbon, status: ?string}  $f
     */
    protected function base(Form $form, array $f): Builder
    {
        $query = DB::table('form_submissions as s')->where('s.form_id', $form->id);

        if ($f['from']) {
            $query->where('s.created_at', '>=', $f['from']);
        }

        if ($f['to']) {
            $query->where('s.created_at', '<=', $f['to']);
        }

        if ($f['status']) {
            $query->where('s.status', $f['status']);
        }

        return $query;
    }

    /**
     * The tiles, in one pass over (form_id, status).
     *
     * min/max created_at ride along because the timeline needs the real span to
     * choose a bucket, and asking for it separately would be a second scan of
     * the same rows.
     *
     * Every column here is on a tile. Nothing is computed "in case" — an
     * aggregate nobody reads is still a column MySQL has to fill on every row of
     * the population, on every dashboard load.
     *
     * @return array<string, mixed>
     */
    protected function totals(Form $form, array $f): array
    {
        $row = $this->base($form, $f)->selectRaw(<<<'SQL'
            count(*) as starts,
            sum(s.status = 'completed') as completed,
            sum(s.status = 'abandoned') as abandoned,
            sum(s.status = 'started') as in_progress,
            sum(s.status = 'spam') as spam,
            sum(s.status = 'validation_failed') as validation_failed,
            sum(s.is_honeypot_triggered = 1) as honeypot,
            avg(case when s.status = 'completed' then s.duration_seconds end) as avg_duration_seconds,
            avg(case when s.status = 'completed' then s.fill_ms end) as avg_fill_ms,
            avg(s.scroll_depth_percent) as avg_scroll_depth,
            sum(s.validation_error_count) as validation_errors,
            min(s.created_at) as first_at,
            max(s.created_at) as last_at
        SQL)->first();

        $starts = (int) ($row->starts ?? 0);
        $completed = (int) ($row->completed ?? 0);

        return [
            'starts' => $starts,
            'completed' => $completed,
            'abandoned' => (int) ($row->abandoned ?? 0),
            'in_progress' => (int) ($row->in_progress ?? 0),
            'spam' => (int) ($row->spam ?? 0),
            'validation_failed' => (int) ($row->validation_failed ?? 0),
            'honeypot' => (int) ($row->honeypot ?? 0),
            'completion_rate' => $this->rate($completed, $starts),
            'avg_duration_seconds' => $this->round($row->avg_duration_seconds ?? null),
            'avg_fill_ms' => $this->round($row->avg_fill_ms ?? null),
            'avg_scroll_depth' => $this->round($row->avg_scroll_depth ?? null),
            'validation_errors' => (int) ($row->validation_errors ?? 0),
            'first_at' => $row->first_at ?? null,
            'last_at' => $row->last_at ?? null,
        ];
    }

    /**
     * Starts and outcomes over time.
     *
     * The bucket widens with the span — a two-year history as daily points is
     * 700 columns nobody can read, and a chart that has to ship them all is
     * slower than the query that produced it. The span is the DATA's, clamped
     * into the requested range: asking for "since 2024" on a form that opened
     * last week draws last week, not two empty years.
     *
     * Gaps are filled in PHP because SQL cannot invent a row for a day nobody
     * submitted, and a line that skips empty days lies about the shape.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function timeline(Form $form, array $f, array $totals): array
    {
        $first = $totals['first_at'] ? Carbon::parse($totals['first_at']) : null;
        $last = $totals['last_at'] ? Carbon::parse($totals['last_at']) : null;

        if (! $first || ! $last) {
            return [];
        }

        $start = ($f['from'] && $f['from']->gt($first) ? $f['from'] : $first)->copy()->startOfDay();
        $end = ($f['to'] && $f['to']->lt($last) ? $f['to'] : $last)->copy()->endOfDay();

        $days = $start->diffInDays($end) + 1;
        [$unit, $expression] = match (true) {
            $days <= 62 => ['day', 'date(s.created_at)'],
            $days <= 400 => ['week', 'date(s.created_at - interval weekday(s.created_at) day)'],
            default => ['month', "date_format(s.created_at, '%Y-%m-01')"],
        };

        $rows = $this->base($form, $f)
            ->selectRaw($expression.<<<'SQL'
                 as bucket,
                count(*) as starts,
                sum(s.status = 'completed') as completed,
                sum(s.status = 'abandoned') as abandoned,
                sum(s.status in ('spam', 'validation_failed')) as rejected
            SQL)
            ->groupByRaw('bucket')
            ->orderByRaw('bucket')
            ->get()
            ->keyBy(fn ($row) => (string) $row->bucket);

        $out = [];
        $cursor = $this->floor($start, $unit);

        while ($cursor->lte($end) && count($out) < $this->maxBuckets) {
            $key = $cursor->toDateString();
            $row = $rows->get($key);

            $out[] = [
                'date' => $key,
                'unit' => $unit,
                'starts' => (int) ($row->starts ?? 0),
                'completed' => (int) ($row->completed ?? 0),
                'abandoned' => (int) ($row->abandoned ?? 0),
                'rejected' => (int) ($row->rejected ?? 0),
            ];

            $cursor = match ($unit) {
                'day' => $cursor->addDay(),
                'week' => $cursor->addWeek(),
                default => $cursor->addMonth(),
            };
        }

        return $out;
    }

    /**
     * The headline: what happened at each field, across every visit.
     *
     * TWO grouped queries, merged on the field key.
     *
     *  1. form_submission_field_events — reached / abandoned / effort, grouped
     *     by field_key rather than by id so a DELETED field keeps its history
     *     (form_field_id is nullOnDelete, the key is snapshotted).
     *  2. form_submissions.validation_errors — a JSON list of the keys that
     *     failed, expanded by JSON_TABLE and counted per key. The per-field
     *     `error_count` column is NOT the source: telemetry writes it as 0
     *     (the public renderer is `novalidate`, so a rejection is a server
     *     round trip), and the submission's list is what actually records one.
     *
     * Live input fields with no events at all are still listed, at zero — a
     * field nobody ever reached is the strongest drop-off signal there is. So
     * are keys that appear in only ONE of the two queries: a deleted field with
     * events, and a deleted field with nothing but failures behind it.
     *
     * Only the columns the dashboard draws are selected. An aggregate nobody
     * renders still costs a pass over every event row on every load.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function fields(Form $form, array $f, int $starts): array
    {
        $events = $this->base($form, $f)
            ->join('form_submission_field_events as e', 'e.form_submission_id', '=', 's.id')
            ->groupBy('e.field_key')
            ->selectRaw(<<<'SQL'
                e.field_key as field_key,
                count(*) as reached,
                sum(e.is_abandoned = 1) as abandoned,
                avg(e.focus_ms) as avg_focus_ms,
                avg(e.revisits) as avg_revisits,
                sum(e.deletions) as corrections
            SQL)
            ->get()
            ->keyBy('field_key');

        /*
         * The stored keys are NAMESPACED, not bare. recordFailure() saves
         * array_keys($validator->errors()), and SubmissionValidator names every
         * rule `fields.{key}` (plus `fields.{key}.*` for arrays and files) — so
         * grouping on the raw value matched nothing and every per-field error
         * count sat at zero while the total above it did not. Normalise here
         * rather than trusting the shape:
         *   fields.name   -> name
         *   fields.cv.0   -> cv
         *   name          -> name   (already bare, left alone)
         */
        $keyExpr = "substring_index(substring_index(jt.k, '.', 2), '.', -1)";

        /*
         * The label for a key the live form no longer has, read out of the
         * submission's OWN snapshot ({key: {label, type}}) so an orphan error
         * reads as a field rather than as a slug.
         *
         * The regexp guard is not decoration: the path is assembled by concat,
         * and a key carrying a quote would make it malformed — MySQL raises on
         * an invalid JSON path rather than returning null, which would 500 the
         * whole dashboard for one bad row.
         *
         * It is usually null today, because recordFailure() writes the error
         * list and no snapshot (only the completed path writes one). That is
         * why the caller falls back to the bare key instead of trusting this —
         * and why the label rides along on THIS query rather than costing a
         * second one for a handful of rows.
         */
        $labelExpr = str_replace('{key}', $keyExpr, <<<'SQL'
            case when {key} regexp '^[A-Za-z0-9_-]+$'
                then nullif(json_unquote(json_extract(s.fields, concat('$."', {key}, '"."label"'))), 'null')
            end
        SQL);

        $errors = $this->base($form, $f)
            ->crossJoin(DB::raw("json_table(s.validation_errors, '\$[*]' columns (k varchar(255) path '\$')) jt"))
            ->whereNotNull('s.validation_errors')
            ->whereRaw('jt.k is not null')
            ->groupBy(DB::raw($keyExpr))
            ->selectRaw("{$keyExpr} as field_key, max({$labelExpr}) as label, count(*) as errors")
            ->get()
            ->keyBy('field_key');

        $locale = Language::defaultCode();
        $out = [];
        $seen = [];

        foreach ($form->pages as $page) {
            $pageName = $page->name ?: ($page->getTranslation('title', $locale) ?: 'Page');

            /*
             * Top-level only. A group's children emit no field events — the
             * telemetry beacon reports one entry per field per submission and
             * form_submission_field_events is unique on exactly that — so listing
             * them here would add a row of zeroes per child and dilute the one
             * number this table exists for: which field loses people.
             */
            foreach ($page->topLevelFields as $field) {
                if (! $field->capturesValue()) {
                    continue;
                }

                $seen[] = $field->key;
                $out[] = $this->fieldRow(
                    $field->key,
                    $this->fieldLabel($field, $locale),
                    $pageName,
                    $events->get($field->key),
                    (int) ($errors->get($field->key)->errors ?? 0),
                    $starts,
                    false,
                );
            }
        }

        // Keys that no longer exist on the form. Kept, and flagged, because the
        // reason a field was removed is usually written in its own numbers.
        foreach ($events as $key => $row) {
            if (in_array($key, $seen, true)) {
                continue;
            }

            $out[] = $this->fieldRow(
                (string) $key,
                (string) $key,
                null,
                $row,
                (int) ($errors->get($key)->errors ?? 0),
                $starts,
                true,
            );
        }

        /*
         * And the keys that failed validation with no live field AND no
         * telemetry behind them. Neither loop above can reach one: the first
         * walks the live form, the second walks the event rows — and a no-JS
         * submission writes the error list and no field events at all, so a
         * since-deleted field's failures land in `$errors` and nowhere else.
         *
         * Those are exactly the errors worth seeing: a form still rejecting
         * submissions over a field that is no longer on it. Dropping them
         * silently is what made the per-field totals disagree with the count on
         * the tile above them.
         */
        foreach ($errors as $key => $row) {
            $key = (string) $key;

            if (in_array($key, $seen, true) || $events->has($key)) {
                continue;
            }

            $label = $row->label ?? null;

            $out[] = $this->fieldRow(
                $key,
                is_string($label) && $label !== '' ? $label : $key,
                null,
                null,
                (int) $row->errors,
                $starts,
                true,
            );
        }

        return $out;
    }

    /**
     * @param  object|null  $row  The aggregated event row, absent when nobody reached it.
     * @return array<string, mixed>
     */
    protected function fieldRow(string $key, string $label, ?string $page, $row, int $errors, int $starts, bool $removed): array
    {
        $reached = (int) ($row->reached ?? 0);
        $abandoned = (int) ($row->abandoned ?? 0);

        return [
            'key' => $key,
            'label' => $label,
            'page' => $page,
            'is_removed' => $removed,
            'reached' => $reached,
            'abandoned' => $abandoned,
            // Of everyone who got as far as this field, the share who stopped ON it.
            'drop_off_rate' => $this->rate($abandoned, $reached),
            'reach_rate' => $this->rate($reached, $starts),
            'avg_focus_ms' => $this->round($row->avg_focus_ms ?? null),
            'avg_revisits' => $this->round($row->avg_revisits ?? null, 2),
            'corrections' => (int) ($row->corrections ?? 0),
            'errors' => $errors,
            // Against every visit, not against `reached`: a no-JS submit writes
            // the error list and no field events at all, so `reached` is not a
            // denominator this can be divided by.
            'error_rate' => $this->rate($errors, $starts),
        ];
    }

    /** The tile: the field that lost the most people. @param array<int, array<string, mixed>> $fields */
    protected function topAbandoned(array $fields): ?array
    {
        $ranked = array_values(array_filter($fields, fn ($field) => $field['abandoned'] > 0));

        usort($ranked, fn ($a, $b) => [$b['abandoned'], $b['drop_off_rate']] <=> [$a['abandoned'], $a['drop_off_rate']]);

        return $ranked[0] ?? null;
    }

    /**
     * Device split AND fill-duration-by-device, from the same grouped scan —
     * they are the same GROUP BY, and running it twice would be a second scan
     * for a second copy of the same numbers.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function devices(Form $form, array $f): array
    {
        // Grouped by an alias that is NOT a column. Repeating the expression
        // fails ONLY_FULL_GROUP_BY on production MySQL (1055), and aliasing it
        // `device_type` would let MySQL resolve the GROUP BY name to the real
        // column — filing NULL and '' as two rows both reading "unknown". So it
        // is selected as `device` and renamed back when mapped.
        return $this->base($form, $f)
            ->groupBy('device')
            ->orderByRaw('starts desc')
            ->selectRaw(<<<'SQL'
                coalesce(nullif(s.device_type, ''), 'unknown') as device,
                count(*) as starts,
                sum(s.status = 'completed') as completed,
                avg(case when s.status = 'completed' then s.duration_seconds end) as avg_duration_seconds,
                avg(case when s.status = 'completed' then s.fill_ms end) as avg_fill_ms,
                avg(s.ttfi_ms) as avg_ttfi_ms
            SQL)
            ->get()
            ->map(fn ($row) => [
                'device_type' => (string) $row->device,
                'starts' => (int) $row->starts,
                'completed' => (int) $row->completed,
                'completion_rate' => $this->rate((int) $row->completed, (int) $row->starts),
                'avg_duration_seconds' => $this->round($row->avg_duration_seconds),
                'avg_fill_ms' => $this->round($row->avg_fill_ms),
                'avg_ttfi_ms' => $this->round($row->avg_ttfi_ms),
            ])
            ->all();
    }

    /**
     * Where the visits came from, by HOST — the full referrer URL would put
     * every article on a site in its own bar.
     *
     * Carries completions as well as starts, which is the referrer x completion
     * rate cross-tab: one query, because both columns come off the same group.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function referrers(Form $form, array $f): array
    {
        $host = <<<'SQL'
            case
                when s.referrer is null or s.referrer = '' then ''
                else trim(leading 'www.' from lower(substring_index(substring_index(substring_index(s.referrer, '://', -1), '/', 1), '?', 1)))
            end
        SQL;

        return $this->base($form, $f)
            ->selectRaw($host." as source, count(*) as starts, sum(s.status = 'completed') as completed")
            ->groupByRaw('source')
            ->orderByRaw('starts desc')
            ->limit($this->topN)
            ->get()
            ->map(fn ($row) => [
                // Empty means no Referer header: typed, bookmarked, or an app.
                'source' => $row->source === '' ? null : (string) $row->source,
                'starts' => (int) $row->starts,
                'completed' => (int) $row->completed,
                'completion_rate' => $this->rate((int) $row->completed, (int) $row->starts),
            ])
            ->all();
    }

    /**
     * Campaign sources, read straight out of the utm JSON column.
     *
     * nullif(…, 'null') is not paranoia: json_unquote of a JSON null yields the
     * four-character string "null", which would otherwise become a campaign.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function utmSources(Form $form, array $f): array
    {
        return $this->base($form, $f)
            ->whereNotNull('s.utm')
            ->selectRaw(<<<'SQL'
                coalesce(nullif(json_unquote(json_extract(s.utm, '$."source"')), 'null'), '') as source,
                coalesce(nullif(json_unquote(json_extract(s.utm, '$."medium"')), 'null'), '') as medium,
                count(*) as starts,
                sum(s.status = 'completed') as completed
            SQL)
            ->groupByRaw('source, medium')
            ->havingRaw("source <> ''")
            ->orderByRaw('starts desc')
            ->limit($this->topN)
            ->get()
            ->map(fn ($row) => [
                'source' => (string) $row->source,
                'medium' => $row->medium === '' ? null : (string) $row->medium,
                'starts' => (int) $row->starts,
                'completed' => (int) $row->completed,
                'completion_rate' => $this->rate((int) $row->completed, (int) $row->starts),
            ])
            ->all();
    }

    /**
     * Average time spent on each page of a multi-page form.
     *
     * `steps` records ARRIVALS — {page, at, direction} — so the time spent on a
     * page is the gap between the hop that left it and the hop before. LAG over
     * (submission, ordinality) is that gap, and the row with no predecessor is
     * the first page, whose id is bound in.
     *
     * JSON_TABLE keeps the whole thing in MySQL. Expanding `steps` in PHP would
     * mean loading every submission's array — the one thing this class does not
     * do.
     *
     * The page a visitor was ON when they left has no closing hop, so it is not
     * counted. That is a floor on every number here, not a gap: an abandoned
     * page's time is unknowable without a hop out of it.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function steps(Form $form, array $f): array
    {
        $pages = $form->pages;

        if ($pages->count() < 2) {
            return [];
        }

        $inner = $this->base($form, $f)
            ->crossJoin(DB::raw(
                "json_table(s.steps, '\$[*]' columns (page varchar(64) path '\$.page', at_ms bigint path '\$.at', ord for ordinality)) jt"
            ))
            ->whereNotNull('s.steps')
            ->selectRaw(
                'coalesce(lag(jt.page) over (partition by s.id order by jt.ord), ?) as page, '
                .'jt.at_ms - coalesce(lag(jt.at_ms) over (partition by s.id order by jt.ord), 0) as dur',
                [$pages->first()->id],
            );

        $measured = DB::query()
            ->fromSub($inner, 't')
            ->whereNotNull('t.page')
            ->whereBetween('t.dur', [0, $this->maxStepMs])
            ->groupBy('t.page')
            ->selectRaw('t.page as page, count(*) as hops, avg(t.dur) as avg_ms')
            ->get()
            ->keyBy('page');

        return $pages->values()->map(function ($page, $index) use ($measured) {
            $row = $measured->get($page->id);

            return [
                'id' => $page->id,
                'name' => $page->name ?: 'Page '.($index + 1),
                'order' => $index + 1,
                'hops' => (int) ($row->hops ?? 0),
                'avg_ms' => $this->round($row->avg_ms ?? null),
            ];
        })->all();
    }

    /**
     * Time-to-submit against the honeypot — the cross-tab that says whether the
     * spam trap is catching machines or people.
     *
     * duration_seconds is only written on the completed path, and a honeypot
     * row never reaches it, so the elapsed time falls back to loaded_at →
     * submitted_at. loaded_at is stamped from the token (page render), which is
     * exactly the clock the min-time rule is judged on.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function honeypot(Form $form, array $f): array
    {
        return $this->base($form, $f)
            ->whereNotNull('s.submitted_at')
            ->groupBy('s.is_honeypot_triggered')
            ->selectRaw(<<<'SQL'
                s.is_honeypot_triggered as triggered,
                count(*) as submissions,
                avg(coalesce(s.duration_seconds, timestampdiff(second, s.loaded_at, s.submitted_at))) as avg_seconds,
                min(coalesce(s.duration_seconds, timestampdiff(second, s.loaded_at, s.submitted_at))) as min_seconds,
                avg(s.ttfi_ms) as avg_ttfi_ms
            SQL)
            ->get()
            ->map(fn ($row) => [
                'triggered' => (bool) $row->triggered,
                'submissions' => (int) $row->submissions,
                'avg_seconds' => $this->round($row->avg_seconds, 1),
                'min_seconds' => $row->min_seconds === null ? null : (int) $row->min_seconds,
                'avg_ttfi_ms' => $this->round($row->avg_ttfi_ms),
            ])
            ->all();
    }

    /* ------------------------------------------------------------------ */

    /** The label an admin reads. Falls back to the machine key, never to blank. */
    protected function fieldLabel(FormField $field, string $locale): string
    {
        $label = $field->getTranslation('label', $locale, false);

        return is_string($label) && $label !== '' ? $label : $field->key;
    }

    /** The start of the bucket `$date` falls in. */
    protected function floor(Carbon $date, string $unit): Carbon
    {
        return match ($unit) {
            'day' => $date->copy()->startOfDay(),
            'week' => $date->copy()->startOfWeek(Carbon::MONDAY),
            default => $date->copy()->startOfMonth(),
        };
    }

    /** A percentage, or null when there is nothing to divide by. */
    protected function rate(int $part, int $whole): ?float
    {
        return $whole > 0 ? round($part / $whole * 100, 1) : null;
    }

    /** MySQL hands averages back as decimal strings; null stays null. */
    protected function round($value, int $precision = 0): int|float|null
    {
        if ($value === null) {
            return null;
        }

        return $precision > 0 ? round((float) $value, $precision) : (int) round((float) $value);
    }
}
