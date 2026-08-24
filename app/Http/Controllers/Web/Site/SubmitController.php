<?php

namespace App\Http\Controllers\Web\Site;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessFormSubmission;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Data\Media\StoreMediaData;
use App\Models\Media;
use App\Services\Analytics\GeoResolver;
use App\Services\Forms\SubmissionContext;
use App\Services\Forms\SubmissionGuard;
use App\Services\Forms\SubmissionToken;
use App\Services\Forms\SubmissionValidator;
use App\Services\Jobs\CvParser;
use App\Services\Forms\TelemetryRecorder;
use App\Services\Integrations\Captcha;
use App\Services\Site\SiteContext;
use App\Services\Uploads\UploadService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The public form's SUBMISSION pipeline.
 *
 * PERMANENT CONTRACT. The JSON schema, the token, the guard order and the stored
 * shape are the contract; the presentation never was. The presentation has since
 * left entirely: the payload assembly went to App\Services\Forms\FormPresenter,
 * and the page a visitor reads is now an ordinary site page owned by
 * Web\Site\FormsController. What is left here is the three POST endpoints and
 * the twelve gates in front of them, at the URIs they have always had — a form
 * already rendered in somebody's browser posts to the action it was served with.
 *
 * Lives in the `web` middleware group on purpose. A session-less group has no
 * ShareErrorsFromSession, so every ValidationException on a non-JSON POST would
 * 500 with "Session store not set" instead of redirecting back with errors.
 */
class SubmitController extends Controller
{
    public function __construct(
        protected SubmissionValidator $validator,
        protected SubmissionGuard $guard,
        protected GeoResolver $geo,
        protected SubmissionContext $context,
        protected TelemetryRecorder $recorder,
    ) {}

    /**
     * Where this form is read: its own page, or the site page that embeds it.
     *
     * A system form has no /forms/{slug} — that route 404s for one — so pointing
     * anything at it would be pointing into a 404. SiteContext owns the slug =>
     * route map, because the menu builder needs the same answer.
     */
    protected function formPageUrl(Form $form, string $locale): string
    {
        if ($form->is_system) {
            $name = SiteContext::SYSTEM_FORM_ROUTES[$form->slug] ?? null;

            return $name === null ? url('/') : route($name, ['locale' => $locale]);
        }

        return route('web.site.forms.show', ['locale' => $locale, 'slug' => $form->slug]);
    }

    /**
     * A file, uploaded before the form is submitted.
     *
     * Its own endpoint rather than multipart on submit, because a file has to
     * survive a page change and a virus scan, and neither fits in the submit
     * request. The answer carries the media id.
     *
     * Five gates, every one narrower than the admin uploader (which is behind
     * verify.auth and 401s an anonymous caller):
     *   1. a valid token bound to THIS form;
     *   2. the field is a real file element on it;
     *   3. the extension is one that field accepts, intersected with the
     *      app-wide allowlist — an admin cannot widen it past config;
     *   4. a per-session cap, so an abandoned form is not free storage;
     *   5. a per-IP throttle on the route.
     *
     * Returns the id and NO URL. Handing a public uploader a link to its own
     * file would turn the form into an anonymous file host.
     */
    public function upload(Request $request, string $slug): JsonResponse
    {
        $form = Form::query()->live()->where('slug', $slug)->with('fields')->firstOrFail();

        $payload = SubmissionToken::read($request->input('submission_token'));

        abort_if(! $payload || $payload['form'] !== $form->id, 422);
        abort_if(SubmissionToken::isStale($payload), 422);

        // The COMPLETE field set on purpose — a file field inside a repeatable
        // group is a child row, and narrowing this to topLevelFields would 422
        // every upload from inside a group. Keys are unique per form, so a child
        // is still found by key alone.
        $field = $form->fields->firstWhere('key', $request->input('field'));

        abort_if(! $field || $field->type !== 'file', 422);

        $used = Media::query()
            ->where('custom_properties->form_session', $payload['sid'])
            ->count();

        abort_if($used >= (int) config('forms.uploads.max_per_session', 10), 429);

        $element = $field->element();
        $extensions = method_exists($element, 'extensions') ? $element->extensions($field) : [];

        $data = StoreMediaData::validateAndCreate([
            'type' => $this->categoryFor($extensions),
            'file' => $request->file('file'),
        ]);

        // The declared extension list is enforced here as well as in the type,
        // because StoreMediaData only knows the broad category.
        if ($extensions !== []) {
            $extension = strtolower($request->file('file')->getClientOriginalExtension());

            abort_if(! in_array($extension, array_map('strtolower', $extensions), true), 422);
        }

        $upload = UploadService::store(
            $data,
            ['form_session' => $payload['sid'], 'form' => $form->id],
            'private:forms',
        );

        return response()->json([
            'id' => $upload->id,
            'name' => $upload->name,
            'size' => $upload->size,
            'scan_status' => $upload->state ?? null,
        ]);
    }

    /**
     * Read an uploaded CV and hand back answers to prefill the form with.
     *
     * SAME GATES AS THE UPLOADER, because it is the same trust boundary: a valid
     * token bound to THIS form, and media carrying THIS visitor's upload session.
     * Without the session check anyone could post a stranger's media id and have
     * their CV read back to them.
     *
     * Costs a provider call, so it sits behind the upload throttle. Any failure
     * answers with empty values rather than an error — the applicant simply gets
     * the blank form they would have had anyway, which is a far better outcome
     * than an error page in front of someone trying to apply.
     */
    public function parseCv(Request $request, string $slug): JsonResponse
    {
        $form = Form::query()->live()->where('slug', $slug)->firstOrFail();

        $payload = SubmissionToken::read($request->input('submission_token'));

        abort_if(! $payload || $payload['form'] !== $form->id, 422);
        abort_if(SubmissionToken::isStale($payload), 422);

        $media = Media::query()
            ->whereKey($request->input('media'))
            ->where('custom_properties->form_session', $payload['sid'])
            ->first();

        abort_if(! $media, 422);

        try {
            $values = app(CvParser::class)->parse($media);
        } catch (Throwable $e) {
            Log::channel('integrations')->warning('forms.cv-parse-failed', [
                'form' => $form->slug,
                'error' => $e->getMessage(),
            ]);

            $values = [];
        }

        return response()->json(['values' => (object) $values]);
    }

    /**
     * Take ownership of every file the visitor uploaded, on the page and inside
     * repeatable groups.
     *
     * Takes TOP-LEVEL fields and walks into groups itself, because a group's
     * files live under its own answer key and the rewritten ids have to go back
     * into the same nested slots they came from.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\FormField>  $fields
     */
    protected function attachFiles(Form $form, $fields, FormSubmission $submission): void
    {
        $data = $submission->data ?? [];
        $changed = false;

        foreach ($fields as $field) {
            if ($field->type === 'file' && array_key_exists($field->key, $data)) {
                $data[$field->key] = $this->ownFiles($data[$field->key], $submission);
                $changed = true;

                continue;
            }

            /*
             * A file inside a repeatable group sits one level down, per instance
             * — data['education'][2]['certificate']. Missing this leaves those
             * uploads unattached: they stay owned by nobody, keep the uploader's
             * form_session, and PublicFormUpload would happily let the NEXT
             * visitor in that session claim them.
             */
            if (! $field->isGroup() || ! is_array($data[$field->key] ?? null)) {
                continue;
            }

            $uploads = $field->children->where('type', 'file');

            if ($uploads->isEmpty()) {
                continue;
            }

            foreach ($data[$field->key] as $index => $instance) {
                foreach ($uploads as $child) {
                    if (! is_array($instance) || ! array_key_exists($child->key, $instance)) {
                        continue;
                    }

                    $data[$field->key][$index][$child->key] = $this->ownFiles($instance[$child->key], $submission);
                    $changed = true;
                }
            }
        }

        if ($changed) {
            $submission->forceFill(['data' => $data])->saveQuietly();
        }
    }

    /**
     * Attach one answer's media to the submission, preserving its shape.
     *
     * attach() COPIES when the media already has an owner and returns the row the
     * model ends up with, so the id it gives back is the one that must be stored —
     * a single-file answer stays a string and a multi-file answer stays a list.
     */
    protected function ownFiles(mixed $answer, FormSubmission $submission): mixed
    {
        $ids = is_array($answer) ? $answer : array_filter([$answer]);
        $owned = [];

        foreach ($ids as $id) {
            $id = is_array($id) ? ($id['id'] ?? null) : $id;

            if (! is_string($id) || $id === '') {
                continue;
            }

            $owned[] = UploadService::attach($id, $submission, FormSubmission::ANSWERS_COLLECTION)->id;
        }

        return is_array($answer) ? $owned : ($owned[0] ?? null);
    }

    /** The upload category covering these extensions. */
    protected function categoryFor(array $extensions): string
    {
        foreach (config('uploads.allowed_types', []) as $category => $allowed) {
            if ($extensions === [] || array_intersect($extensions, array_map('strtolower', $allowed)) !== []) {
                return $category;
            }
        }

        return 'documents';
    }

    /**
     * The analytics beacon.
     *
     * THE TOKEN IS THE ONLY GATE. This route is excepted from CSRF because
     * navigator.sendBeacon cannot set a header and therefore can never carry the
     * token — so the encrypted, form-bound, time-limited submission token is
     * what stands between this endpoint and anybody at all, plus a per-IP
     * throttle on the route and a per-IP draft cap inside the recorder.
     *
     * It writes NO ANSWERS. There is no field on the wire that could carry one:
     * the payload is counts and timings, and a draft's `data` stays null until
     * the visitor actually submits.
     *
     * ALWAYS 204 on anything that is not a bad token. A beacon is fire-and-
     * forget — the browser has usually navigated away before the response
     * arrives, and nothing is listening. The 422s exist for the developer
     * watching the network tab, not for the client.
     */
    public function telemetry(Request $request, string $slug): Response
    {
        /*
         * A BEACON MUST BE TRANSPARENT TO THE FLASH, and this line is the whole
         * of what makes it so.
         *
         * Flash data survives exactly one request: Store::ageFlashData() runs on
         * every save, moving `new` to `old` and FORGETTING whatever was already
         * in `old`. This endpoint is in the `web` group, so it has a session and
         * it ages one.
         *
         * That is a race with every redirect the form does. pagehide fires as a
         * submit navigates, so a beacon can land AFTER the POST that flashed and
         * BEFORE the GET that reads it — and the confirmation, the validation
         * errors and the old input all vanish, intermittently, on the visitors
         * whose browser happened to win. It is invisible to a scripted test,
         * which sends no beacons at all.
         *
         * reflash() keeps whatever is in flight for one more request, so a
         * beacon costs the page nothing. First statement in the method because
         * the guards below abort.
         */
        $request->session()->reflash();

        $form = Form::query()->live()->where('slug', $slug)
            ->with(['blockedIps', 'pages', 'fields'])
            ->first();

        // A form taken offline mid-visit still has pages open against it. Their
        // beacons are dropped rather than 404'd.
        if (! $form) {
            return response()->noContent();
        }

        $payload = SubmissionToken::read($request->input('submission_token'));

        abort_if(! $payload || $payload['form'] !== $form->id, 422);
        abort_if(SubmissionToken::isStale($payload), 422);

        // Same gates as the GET: measuring a visit nobody may submit is just a
        // slower rejection, and a blocked origin must not be able to write rows.
        if ($this->guard->blocksCountry($form, $this->geo->countryCode($request))
            || $this->guard->blocksIp($form, $request->ip())) {
            abort(403);
        }

        $this->recorder->record($request, $form, $request->all(), $payload);

        return response()->noContent();
    }

    /**
     * The submit pipeline. The order is the design — see SubmissionGuard.
     */
    public function submit(Request $request): RedirectResponse
    {
        /*
         * BOTH READ BY NAME, NEVER AS METHOD ARGUMENTS.
         *
         * This one action serves two routes — `{locale}/forms/{slug}` and the
         * stripped `forms/{slug}` — and Laravel binds non-class controller
         * parameters POSITIONALLY, not by name. A `submit(string $locale, string
         * $slug)` signature is handed the SLUG as its first argument on the
         * unprefixed route, and 404s hunting a form called "en" on the other.
         * Same trap the site controllers carry a note about.
         *
         * The locale comes from the app rather than the URI because SetLocale
         * has already resolved it: it reads the segment when there is one, falls
         * back to the default when there is not, and 404s anything that is not
         * an enabled code — so there is nothing left here to re-check.
         */
        $slug = $request->route('slug');
        $locale = App::getLocale();

        // 1. Live?
        /*
         * topLevelFields, not fields: a group compiles and stores its own
         * children, so the submit path must not also walk them as if they sat on
         * the page. `children.options` is loaded alongside so a whole form's
         * rules — a select inside a repeat included — cost one query, not one per
         * child.
         */
        $form = Form::query()->live()->where('slug', $slug)
            ->with(['blockedIps', 'topLevelFields.options', 'topLevelFields.children.options'])
            ->firstOrFail();

        // 2. Authentic, form-bound token?
        $payload = SubmissionToken::read($request->input('submission_token'));

        if (! $payload || $payload['form'] !== $form->id) {
            $this->recordSpam($request, $form, ['forged_token'], 60);

            return back()->withErrors(['form' => __('forms.invalid_session')])->withInput();
        }

        // 3. Fresh?
        if (SubmissionToken::isStale($payload)) {
            return back()->withErrors(['form' => __('forms.expired')])->withInput();
        }

        $ip = $request->ip();
        $ipHash = $this->guard->hashIp($ip);
        $fingerprint = $this->guard->fingerprint($request, (array) $request->input('client', []));
        $countryCode = $this->geo->countryCode($request);

        // 4. Country and IP.
        if ($this->guard->blocksCountry($form, $countryCode) || $this->guard->blocksIp($form, $ip)) {
            $this->recordSpam($request, $form, ['blocked_origin'], 100);

            abort(403);
        }

        // 5/6. Caps.
        if ($form->hasReachedLimit()) {
            return back()->withErrors(['form' => __('forms.closed')])->withInput();
        }

        if ($this->guard->overPerUserLimit($form, $ipHash, $fingerprint)) {
            return back()->withErrors(['form' => __('forms.already_submitted')])->withInput();
        }

        $answers = (array) $request->input('fields', []);

        // 7/8. Honeypot and minimum time. Both render the confirmation, so a bot
        // learns nothing — which is precisely why the row must be written.
        if ($form->is_spam_filtered) {
            if ($this->guard->honeypotTripped($request, $payload['hp'])) {
                $this->recordSpam($request, $form, ['honeypot'], 100, [], true);

                return $this->confirmation($form, $locale);
            }

            if (SubmissionToken::elapsed($payload) < (int) $payload['min']) {
                // Values kept: a fast human on a one-field form is real, and
                // this is the row someone will want to review.
                $this->recordSpam($request, $form, ['too_fast'], 70, $answers);

                return $this->confirmation($form, $locale);
            }
        }

        // 9. Captcha.
        $captcha = Captcha::forForm($form);

        if ($captcha->enabled()) {
            /*
             * Either name. The renderer mirrors the token into `captcha_token`,
             * which is the field that works for every provider and version —
             * reCAPTCHA v3 injects nothing to post. `g-recaptcha-response` is
             * the textarea v2's widget injects and posts by itself, kept as a
             * fallback so a solved challenge still verifies if the mirror ever
             * fails. Type-checked because a caller can post either as an array.
             */
            $token = $request->input('captcha_token') ?: $request->input('g-recaptcha-response');

            $result = $captcha->verify(is_string($token) ? $token : null, $ip);

            /*
             * FAILS OPEN when the provider is unreachable, and only then.
             *
             * CaptchaResultData separates `failed` from `unavailable` precisely so
             * this line can: failed means the challenge was answered and the answer
             * was wrong (or a v3 score under the threshold, or no token at all —
             * Captcha::verify() returns failed, not unavailable, for a blank one),
             * while unavailable means we never got an answer out of Google at all.
             *
             * Blocking on unavailable makes a Google outage take every
             * captcha-protected form offline, for every real visitor, until Google
             * comes back. Letting those through costs the captcha for the length of
             * the outage — and only the captcha: the honeypot, the minimum fill
             * time, the per-user and per-form caps, the country and IP blocks and
             * the route throttle all still run, and none of them depend on Google.
             * A guaranteed outage for humans is the worse trade.
             *
             * Logged, not silent: a form quietly losing its bot protection has to
             * be visible to whoever reads the integrations channel.
             */
            if ($result->unavailable) {
                Log::channel('integrations')->warning('Captcha unavailable — submission allowed through.', ['form' => $form->id]);
            } elseif (! $result->ok) {
                return back()->withErrors(['form' => __('validation.captcha')])->withInput();
            }
        }

        // 10. The answers themselves.
        $fields = $form->topLevelFields;
        $validator = $this->validator->make($form, $fields, $answers, $locale, $payload['sid']);

        if ($validator->fails()) {
            // Error KEYS only — the values are the visitor's data and this row
            // exists to count failures, not to keep a copy of what they typed.
            $this->recordFailure($request, $form, array_keys($validator->errors()->toArray()));

            return back()->withErrors($validator)->withInput();
        }

        // 11. Persist.
        $submission = DB::transaction(function () use ($form, $fields, $answers, $locale, $request, $ip, $ipHash, $fingerprint, $countryCode, $payload) {
            // Re-read under a lock: two concurrent submits would otherwise both
            // see room under the cap and both take it.
            $locked = Form::whereKey($form->id)->lockForUpdate()->first();

            if ($locked->hasReachedLimit()) {
                return null;
            }

            $previousStatus = null;

            $submission = $this->newSubmission($request, $form, [
                'status' => 'completed',
                'session_id' => $payload['sid'],
                'submitted_at' => now(),
                'duration_seconds' => SubmissionToken::elapsed($payload),
                'data' => $this->validator->normalise($fields, $answers),
                'fields' => $this->validator->snapshot($fields, $locale),
                'ip_hash' => $ipHash,
                'ip_address' => $form->is_ip_stored ? $ip : null,
                'fingerprint' => $fingerprint,
                'country_code' => $countryCode,
                // The draft this claims was very probably still pointing at the
                // field the visitor had open when the last beacon left. They
                // finished; nothing here was abandoned.
                'abandoned_form_field_id' => null,
                'abandoned_field_key' => null,
            ], $previousStatus);

            $this->recorder->clearAbandonment($submission);

            $this->attachFiles($form, $fields, $submission);

            /*
             * COUNT THE COMPLETION, NOT THE REQUEST.
             *
             * One session owns one row, so a second submit from a page still
             * holding its first token re-claims the row it already completed
             * rather than inserting. Incrementing unconditionally would then
             * charge that one submission twice against `submissions_limit` and
             * close the form early — a double-click costing a place.
             */
            if ($previousStatus !== 'completed') {
                $locked->increment('submissions_count');
            }

            return $submission;
        });

        if ($submission === null) {
            return back()->withErrors(['form' => __('forms.closed')])->withInput();
        }

        // 12. Side effects, AFTER the commit.
        $this->dispatchSideEffects($form, $submission);

        return $this->confirmation($form, $locale, $submission);
    }

    /**
     * Hand the side effects off to run AFTER the response is flushed.
     *
     * Deliberately not in FormSubmissionObserver::created(): that fires inside
     * the transaction, so a rollback would leave notifications already sent for
     * a submission that no longer exists. And deliberately not inline here
     * either - on a sync queue that would make the visitor wait on SMTP and
     * webhook round-trips for a form they have already submitted.
     */
    protected function dispatchSideEffects(Form $form, FormSubmission $submission): void
    {
        ProcessFormSubmission::dispatchAfterResponse($submission->id);
    }

    /**
     * Back to the page they submitted from, or away when the form asks for it.
     *
     * THERE IS NO CONFIRMATION URL any more. The visitor returns to the page
     * they were already on — the form's own page, or /contact and /inquiries,
     * which embed the same renderer — and site::partials.forms.embed reads these
     * two flashes and draws the confirmation in place of the form. One page, one
     * address, whichever way the form was reached.
     *
     * The fallback is load-bearing: back() with no previous URL lands on '/',
     * which has no embed partial on it, so the flash would be set and then
     * silently dropped. "No previous URL" is also the shape of a post with no
     * session, which is to say a bot.
     *
     * Keyed by form id rather than a bare boolean, because a page may embed more
     * than one form and only the submitted one may confirm.
     *
     * The submission's ULID IS the reference — there is no second code to quote.
     * It is null on the honeypot and too-fast paths, which is exactly what keeps
     * those out of the conversion count while showing an identical page.
     */
    protected function confirmation(Form $form, string $locale, ?FormSubmission $submission = null): RedirectResponse
    {
        if ($form->confirmation_type === 'redirect' && $form->redirect_url) {
            return redirect()->away($form->redirect_url);
        }

        return back(303, [], $this->formPageUrl($form, $locale))
            ->with('sisf_submitted', $form->id)
            ->with('sisf_reference', $submission?->id);
    }

    /* ------------------------------------------------------------------ */

    /**
     * The row for this outcome — UPGRADING the session's draft when there is one.
     *
     * A visit that produced telemetry already has a `started` row, created by the
     * first beacon. Writing a second row here would double every visitor in the
     * funnel: one abandoned draft plus one real outcome, for a person who did
     * exactly one thing. So the draft is claimed instead, and only a session that
     * never beaconed (JavaScript off, a forged token, a very fast bot) creates a
     * row from nothing.
     *
     * The session comes from the token rather than a parameter, so every caller
     * gets this without having to remember to pass it. A null or forged token
     * yields no session and therefore no draft to claim, which is correct: there
     * is nothing to say it was the same visit.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function newSubmission(Request $request, Form $form, array $attributes, ?string &$previousStatus = null): FormSubmission
    {
        $previousStatus = null;

        $token = SubmissionToken::read($request->input('submission_token'));
        $session = $token && $token['form'] === $form->id ? $token['sid'] : null;

        $defaults = array_merge($this->context->all($request, (array) $request->input('client', [])), [
            'form_id' => $form->id,
            // Measured from the token, so it is when the page was RENDERED rather
            // than when it was posted — the difference is the whole visit.
            'loaded_at' => $token ? now()->subSeconds(SubmissionToken::elapsed($token)) : now(),
        ]);

        if ($session) {
            $existing = $this->sessionRow($form, $session);

            if ($existing) {
                return $this->claim($existing, $defaults, $attributes, $previousStatus);
            }
        }

        /*
         * The INSERT can still lose a race the SELECT above could not see. The
         * beacon fires on pagehide, which is precisely when a submit navigates
         * away, so it lands between the two often enough to matter.
         *
         * The unique index makes the loser fail rather than duplicate, and this
         * adopts the row the winner created rather than 500ing on it. Same shape
         * as TelemetryRecorder::record(), deliberately: both writers race for the
         * same single row, so both have to survive losing.
         */
        try {
            return FormSubmission::create(array_merge($defaults, $attributes));
        } catch (UniqueConstraintViolationException $e) {
            $existing = $session ? $this->sessionRow($form, $session) : null;

            /*
             * A null session cannot collide on (form_id, session_id) — MySQL
             * allows any number of NULLs in a unique index — so a violation with
             * no session is some other constraint entirely and must not be
             * swallowed into a misleading "adopted the other row".
             */
            if (! $existing) {
                throw $e;
            }

            return $this->claim($existing, $defaults, $attributes, $previousStatus);
        }
    }

    /**
     * The one row this session is allowed, WHATEVER state it reached.
     *
     * DELIBERATELY NOT FILTERED TO `started`, which is what it used to do and
     * what made this throw. The unique index is on (form_id, session_id) and
     * says nothing about status, so a lookup narrower than the constraint finds
     * nothing, inserts, and hits the index anyway —
     * SQLSTATE[23000] 1062 straight out of a public page.
     *
     * Every path that reuses a session hit it: a second submit from a page still
     * holding its first token (a double-click, or a browser Back and re-post),
     * and a beacon whose draft had already been aged to `abandoned` before the
     * visitor got round to finishing.
     */
    protected function sessionRow(Form $form, string $session): ?FormSubmission
    {
        return FormSubmission::query()
            ->where('form_id', $form->id)
            ->where('session_id', $session)
            ->first();
    }

    /**
     * Take this session's row over and record the outcome on it.
     *
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $attributes
     * @param  string|null  $previousStatus  set to the status this row held before the claim
     */
    protected function claim(FormSubmission $row, array $defaults, array $attributes, ?string &$previousStatus = null): FormSubmission
    {
        $previousStatus = $row->status;

        /*
         * The row was very probably still pointing at whatever field the visitor
         * had open when the last beacon left. Reaching ANY terminal state ends
         * that — a submission that was rejected as spam or failed validation was
         * not abandoned at that field, and leaving the marker set inflates the
         * drop-off chart with the field people actually finished on.
         *
         * Here rather than on the completed branch alone, because this method is
         * the one funnel every terminal outcome goes through.
         */
        $terminal = ($attributes['status'] ?? null) !== 'started';

        if ($terminal) {
            $attributes += ['abandoned_form_field_id' => null, 'abandoned_field_key' => null];
        }

        $row->forceFill(array_merge($defaults, $attributes))->save();

        if ($terminal) {
            $this->recorder->clearAbandonment($row);
        }

        return $row;
    }

    /** @param array<int, string> $reasons */
    protected function recordSpam(Request $request, Form $form, array $reasons, int $score, array $answers = [], bool $honeypot = false): void
    {
        $this->newSubmission($request, $form, [
            'status' => 'spam',
            // Clamped: the reason table sums past a tinyint, and an overflow
            // would throw under strict mode and 500 the public path.
            'spam_score' => min(100, $score),
            'spam_reasons' => $reasons,
            'is_honeypot_triggered' => $honeypot,
            'submitted_at' => now(),
            'data' => $answers ?: null,
            'ip_hash' => $this->guard->hashIp($request->ip()),
            'ip_address' => $form->is_ip_stored ? $request->ip() : null,
        ]);
    }

    /** @param array<int, string> $keys */
    protected function recordFailure(Request $request, Form $form, array $keys): void
    {
        $this->newSubmission($request, $form, [
            'status' => 'validation_failed',
            'validation_errors' => $keys,
            'validation_error_count' => count($keys),
            'submitted_at' => now(),
            'ip_hash' => $this->guard->hashIp($request->ip()),
            'ip_address' => $form->is_ip_stored ? $request->ip() : null,
        ]);
    }

}
