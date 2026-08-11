<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessFormSubmission;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Data\Media\StoreMediaData;
use App\Models\Language;
use App\Models\Media;
use App\Services\Forms\FormPresenter;
use App\Services\Forms\GeoResolver;
use App\Services\Forms\SubmissionContext;
use App\Services\Forms\SubmissionGuard;
use App\Services\Forms\SubmissionToken;
use App\Services\Forms\SubmissionValidator;
use App\Services\Forms\TelemetryRecorder;
use App\Services\Integrations\Captcha;
use App\Services\Uploads\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * The public form.
 *
 * PERMANENT CONTRACT. The JSON schema, the token, the guard order and the stored
 * shape are the contract; the presentation never was. The views have since been
 * re-parented onto the real site layout and the payload assembly extracted to
 * App\Services\Forms\FormPresenter — which the site's own contact and admissions
 * pages also call, so one renderer serves both. None of the contract moved.
 *
 * Lives in the `web` middleware group on purpose. A session-less group has no
 * ShareErrorsFromSession, so every ValidationException on a non-JSON POST would
 * 500 with "Session store not set" instead of redirecting back with errors.
 */
class FormsController extends Controller
{
    public function __construct(
        protected SubmissionValidator $validator,
        protected SubmissionGuard $guard,
        protected GeoResolver $geo,
        protected SubmissionContext $context,
        protected TelemetryRecorder $recorder,
        protected FormPresenter $presenter,
    ) {}

    /**
     * The form itself. Locale is an explicit URL segment, never a negotiation.
     *
     * The assembly moved to FormPresenter when the site's contact and admissions
     * pages started embedding the same renderer. The GUARDS and their status
     * codes stayed here, because those are this controller's contract.
     */
    public function show(Request $request, string $locale, string $slug): View|RedirectResponse
    {
        abort_unless(in_array($locale, Language::enabledCodes(), true), 404);

        $form = Form::query()->live()->where('slug', $slug)->firstOrFail();

        App::setLocale($locale);

        $state = $this->presenter->state($request, $form);

        $title = $form->getTranslation('title', $locale, true) ?: $form->name;

        if ($state === 'blocked') {
            return response()->view('site::forms.blocked', [
                'form' => $form,
                'locale' => $locale,
                'seo' => $this->seo($form, $locale, $title, 'noindex,nofollow'),
            ], 403);
        }

        if ($state === 'closed') {
            return response()->view('site::forms.closed', [
                'form' => $form,
                'locale' => $locale,
                'seo' => $this->seo($form, $locale, $title, 'noindex,nofollow'),
            ], 410);
        }

        $presentation = $this->presenter->present($form, $locale);

        return view('site::forms.show', [
            'form' => $form,
            'locale' => $locale,
            'presentation' => $presentation,
            'seo' => $this->seo($form, $locale, $presentation->schema['title'][$locale] ?? $title),
        ]);
    }

    /**
     * The <head> contract site::layout reads, for the four standalone form pages.
     *
     * A helper and not four inline copies, unlike the site's own controllers:
     * these are four views of ONE resource at one URL — the form — rather than
     * four pages, and `show()` alone picks between three of them on the way out.
     *
     * @return array<string, mixed>
     */
    protected function seo(Form $form, string $locale, string $title, string $robots = 'index,follow'): array
    {
        $routeName = 'web.user.forms.show';
        $routeParameters = ['slug' => $form->slug];

        return [
            'title' => $title,
            'description' => $form->getTranslation('description', $locale, true),
            'image' => null,
            'canonical' => route($routeName, ['locale' => $locale] + $routeParameters),
            'robots' => $robots,
            'type' => 'website',
            // These URIs keep their own /forms/{locale}/{slug} shape, so the
            // locale is an explicit parameter here and never stripped.
            'alternates' => collect(Language::enabledCodes())->mapWithKeys(fn (string $code) => [
                $code => route($routeName, ['locale' => $code] + $routeParameters),
            ]),
        ];
    }

    /** Bare slug: send the visitor to a locale they can read. */
    public function redirectToLocale(Request $request, string $slug): RedirectResponse
    {
        $enabled = Language::enabledCodes();
        $preferred = $request->getPreferredLanguage($enabled) ?: Language::defaultCode();

        return redirect()->route('web.user.forms.show', ['locale' => $preferred, 'slug' => $slug]);
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
    public function upload(Request $request, string $locale, string $slug): JsonResponse
    {
        abort_unless(in_array($locale, Language::enabledCodes(), true), 404);

        $form = Form::query()->live()->where('slug', $slug)->with('fields')->firstOrFail();

        $payload = SubmissionToken::read($request->input('submission_token'));

        abort_if(! $payload || $payload['form'] !== $form->id, 422);
        abort_if(SubmissionToken::isStale($payload), 422);

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
     * Take ownership of the files the visitor uploaded.
     *
     * attach() COPIES when the media already has an owner, and returns the row
     * the model ends up with — so the returned id is written back into the
     * answer. Skipping that would leave a retried submission pointing at the
     * previous submission's file.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\FormField>  $fields
     */
    protected function attachFiles(Form $form, $fields, FormSubmission $submission): void
    {
        $data = $submission->data ?? [];
        $changed = false;

        foreach ($fields as $field) {
            if ($field->type !== 'file' || ! array_key_exists($field->key, $data)) {
                continue;
            }

            $ids = is_array($data[$field->key]) ? $data[$field->key] : array_filter([$data[$field->key]]);
            $owned = [];

            foreach ($ids as $id) {
                $id = is_array($id) ? ($id['id'] ?? null) : $id;

                if (! is_string($id) || $id === '') {
                    continue;
                }

                $owned[] = UploadService::attach($id, $submission, FormSubmission::ANSWERS_COLLECTION)->id;
            }

            $data[$field->key] = is_array($data[$field->key]) ? $owned : ($owned[0] ?? null);
            $changed = true;
        }

        if ($changed) {
            $submission->forceFill(['data' => $data])->saveQuietly();
        }
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
    public function telemetry(Request $request, string $locale, string $slug): Response
    {
        abort_unless(in_array($locale, Language::enabledCodes(), true), 404);

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
    public function submit(Request $request, string $locale, string $slug): RedirectResponse
    {
        abort_unless(in_array($locale, Language::enabledCodes(), true), 404);

        App::setLocale($locale);

        // 1. Live?
        $form = Form::query()->live()->where('slug', $slug)->with(['blockedIps', 'fields.options'])->firstOrFail();

        // 2. Authentic, form-bound token?
        $payload = SubmissionToken::read($request->input('submission_token'));

        if (! $payload || $payload['form'] !== $form->id) {
            $this->recordSpam($request, $form, $locale, ['forged_token'], 60);

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
            $this->recordSpam($request, $form, $locale, ['blocked_origin'], 100);

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
                $this->recordSpam($request, $form, $locale, ['honeypot'], 100, [], true);

                return $this->confirmation($form, $locale);
            }

            if (SubmissionToken::elapsed($payload) < (int) $payload['min']) {
                // Values kept: a fast human on a one-field form is real, and
                // this is the row someone will want to review.
                $this->recordSpam($request, $form, $locale, ['too_fast'], 70, $answers);

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
        $fields = $form->fields;
        $validator = $this->validator->make($form, $fields, $answers, $locale, $payload['sid']);

        if ($validator->fails()) {
            // Error KEYS only — the values are the visitor's data and this row
            // exists to count failures, not to keep a copy of what they typed.
            $this->recordFailure($request, $form, $locale, array_keys($validator->errors()->toArray()));

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

            $submission = $this->newSubmission($request, $form, $locale, [
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
            ]);

            $this->recorder->clearAbandonment($submission);

            $this->attachFiles($form, $fields, $submission);

            $locked->increment('submissions_count');

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

    /** The thank-you page, or a redirect when the form asks for one. */
    protected function confirmation(Form $form, string $locale, ?FormSubmission $submission = null): RedirectResponse
    {
        if ($form->confirmation_type === 'redirect' && $form->redirect_url) {
            return redirect()->away($form->redirect_url);
        }

        // The ULID id IS the reference — there is no second code to quote.
        return redirect()
            ->route('web.user.forms.thanks', ['locale' => $locale, 'slug' => $form->slug])
            ->with('sisf_reference', $submission?->id);
    }

    public function thanks(Request $request, string $locale, string $slug): View
    {
        abort_unless(in_array($locale, Language::enabledCodes(), true), 404);

        App::setLocale($locale);

        $form = Form::query()->live()->where('slug', $slug)->firstOrFail();

        return view('site::forms.thanks', [
            'form' => $form,
            'locale' => $locale,
            'reference' => session('sisf_reference'),
            'seo' => $this->seo($form, $locale, __('forms.thanks_title'), 'noindex,nofollow'),
        ]);
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
    protected function newSubmission(Request $request, Form $form, string $locale, array $attributes): FormSubmission
    {
        $token = SubmissionToken::read($request->input('submission_token'));
        $session = $token && $token['form'] === $form->id ? $token['sid'] : null;

        $defaults = array_merge($this->context->all($request, (array) $request->input('client', [])), [
            'form_id' => $form->id,
            // Measured from the token, so it is when the page was RENDERED rather
            // than when it was posted — the difference is the whole visit.
            'loaded_at' => $token ? now()->subSeconds(SubmissionToken::elapsed($token)) : now(),
        ]);

        if ($session) {
            $draft = FormSubmission::query()
                ->where('form_id', $form->id)
                ->where('session_id', $session)
                ->where('status', 'started')
                ->first();

            if ($draft) {
                /*
                 * The draft was very probably still pointing at whatever field
                 * the visitor had open when the last beacon left. Reaching ANY
                 * terminal state ends that — a submission that was rejected as
                 * spam or failed validation was not abandoned at that field, and
                 * leaving the marker set inflates the drop-off chart with the
                 * field people actually finished on.
                 *
                 * Here rather than on the completed branch alone, because this
                 * method is the one funnel every terminal outcome goes through.
                 */
                $terminal = ($attributes['status'] ?? null) !== 'started';

                if ($terminal) {
                    $attributes += ['abandoned_form_field_id' => null, 'abandoned_field_key' => null];
                }

                $draft->forceFill(array_merge($defaults, $attributes))->save();

                if ($terminal) {
                    $this->recorder->clearAbandonment($draft);
                }

                return $draft;
            }
        }

        return FormSubmission::create(array_merge($defaults, $attributes));
    }

    /** @param array<int, string> $reasons */
    protected function recordSpam(Request $request, Form $form, string $locale, array $reasons, int $score, array $answers = [], bool $honeypot = false): void
    {
        $this->newSubmission($request, $form, $locale, [
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
    protected function recordFailure(Request $request, Form $form, string $locale, array $keys): void
    {
        $this->newSubmission($request, $form, $locale, [
            'status' => 'validation_failed',
            'validation_errors' => $keys,
            'validation_error_count' => count($keys),
            'submitted_at' => now(),
            'ip_hash' => $this->guard->hashIp($request->ip()),
            'ip_address' => $form->is_ip_stored ? $request->ip() : null,
        ]);
    }

}
