{{--
    The public form renderer's mount point and its payload.

    ONE COPY OF THE PAYLOAD CONTRACT. The form's own page and the site's contact
    and inquiries pages all reach this through site::partials.form-embed, so the
    schema shape, the token, the honeypot and the captcha wiring cannot drift
    between them.

    Expects: $presentation (App\Services\Forms\FormPresentation), and optionally
    $chrome (see below).
--}}
@php
    $form = $presentation->form;
    $captcha = $presentation->captcha;

    /*
     * Whether the renderer prints the form's own title, description and content.
     *
     * FALSE on the form's own page, where those three ARE the page — its hero
     * and its body — and printing them again inside the form would say
     * everything twice. TRUE everywhere else, which is what /contact and
     * /inquiries have always rendered.
     */
    $chrome = $chrome ?? true;

    /*
     * Answers the PAGE knows before the visitor does — today, the hidden
     * job_offer_id on a posting's apply form.
     *
     * old() WINS. A rejected submit must give the visitor back exactly what they
     * typed, and a preset that overrode it would silently rewrite an answer they
     * are looking at. Presets only fill what nothing else has filled.
     */
    $presets = $presets ?? [];

    /*
     * Whether this form opens with a choice — fill it in, or upload a CV and have
     * it prefilled — rather than with its first field. Off unless the page that
     * embeds the form asks for it, so /contact is untouched.
     */
    $chooser = $chooser ?? false;

    /*
     * Per-field errors, keyed the way the renderer looks them up: by field key.
     *
     * The validator namespaces answers under `fields.` so a field called
     * `captcha_token` cannot collide with the real one, and an array answer
     * reports per element (`fields.docs.0`). Both are folded back onto the field
     * itself, because the field is the only thing the renderer can highlight.
     */
    $fieldErrors = [];

    foreach ($errors->getBag('default')->messages() as $errorKey => $errorMessages) {
        if (! str_starts_with($errorKey, 'fields.')) {
            continue;
        }

        $segments = explode('.', substr($errorKey, strlen('fields.')));

        /*
         * A repeatable group's child reports at `group.index.child` (plus a
         * trailing index of its own when the child answer is itself an array).
         * THAT PATH IS KEPT WHOLE, because it is the only thing that says WHICH
         * ROW was wrong — folding it to the group would flag "Education" and
         * leave the visitor to work out which of five entries to fix.
         */
        if (count($segments) >= 3) {
            $fieldErrors[implode('.', array_slice($segments, 0, 3))] ??= $errorMessages[0] ?? '';

            continue;
        }

        // First wins: a rule on the field is reported before the rules on its
        // individual values, and it is the more useful of the two.
        $fieldErrors[$segments[0]] ??= $errorMessages[0] ?? '';
    }

    // Not a field: an expired or forged token, a form that filled up, a rejected
    // captcha. '' when there is none — first() never returns null.
    $formError = $errors->first('form');
@endphp

{{--
    The FORM-LEVEL error only — an expired or forged token, a form that filled up
    or was already submitted, a failed captcha. Every other message belongs to a
    field and is rendered in red underneath it by the island; printing the whole
    bag here as well, which is what this used to do, said each one twice.
--}}
@if ($formError)
    <div class="alert alert-danger mb-4 flex items-center gap-2" role="alert">
        <i class="uil uil-exclamation-triangle" aria-hidden="true"></i>
        <span class="flex-1">{{ $formError }}</span>
    </div>
@endif

{{--
    NO <form> HERE. FormRenderer renders the real one, because only it knows
    which answers exist across pages the visitor is not currently looking at
    — it mirrors the whole set into hidden inputs. Wrapping it in a second
    form would nest them, and the browser drops the inner one along with
    every one of those hidden answers.
--}}
<div data-sisf-root>
    <noscript>
        <p>{{ __('forms.javascript_required') }}</p>
    </noscript>
</div>

{{-- JSON blocks, not JS variables: the schema carries admin-authored HTML,
     and a stray </script> inside a string literal would break out of it. --}}
<script type="application/json" id="sisf-schema">
    {!! json_encode($presentation->schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}
</script>

<script type="application/json" id="sisf-config">
    {!! json_encode([
        'action' => $presentation->action,
        'csrf' => csrf_token(),
        'token' => $presentation->token,
        'uploadAction' => $presentation->uploadAction,
        'chooser' => $chooser,
        // Whether the visitor is coming BACK from a rejected submit. Not derived
        // from `old` below, because presets are merged into it — a page that
        // supplies one would otherwise look like a returning visitor forever and
        // the chooser would never appear.
        'returning' => old('fields', []) !== [],
        'parseAction' => route('web.user.forms.parse-cv', ['slug' => $form->slug]),
        'cvKey' => config('jobs.cv_field'),
        'telemetryAction' => $presentation->telemetryAction,
        // What the page is allowed to measure. Sent rather than compiled in,
        // so switching a capture off in .env switches off the LISTENER, not
        // just the column — nothing is collected that is not sent.
        'capture' => config('forms.capture'),
        'honeypot' => $presentation->honeypot,
        'old' => (object) array_merge($presets, old('fields', [])),
        // (object), like `old` above: an empty [] serialises as a JSON array,
        // which is truthy in JS and would sail past the renderer's `?? {}`.
        'errors' => (object) $fieldErrors,
        'chrome' => $chrome,
        // The renderer's own chrome, translated server-side. It used to hardcode
        // "Next"/"Back"/"Submit" as English literals, which showed through on
        // every Arabic form.
        'labels' => $presentation->labels,
        // Null on a form with no challenge — the renderer draws nothing and
        // posts no token, which is the case the server skips entirely.
        'captcha' => $captcha->enabled() ? [
            'provider' => $captcha->provider(),
            'siteKey' => $captcha->siteKey(),
            // Defaulted here rather than in the renderer, to match the
            // driver's own fallback when an old row has no version set.
            'version' => $captcha->version() ?: 'v2',
        ] : null,
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}
</script>

{{-- The provider script, and ONLY when this form actually challenges: a form
     with no captcha must not reach out to Google at all.

     v3 wants the site key baked into the URL; v2 is rendered explicitly by
     the island, because the widget belongs inside the <form> the renderer
     owns and auto-render would have to find a div that does not exist yet.

     urlencode(), not {{ }} alone: Blade escapes for HTML, which leaves & and
     # intact — and either one inside a site key would end the `render`
     parameter early and load the script against a truncated key. --}}
@if ($captcha->provider() === 'recaptcha' && $captcha->siteKey())
    <script
        src="https://www.google.com/recaptcha/api.js?render={{ $captcha->version() === 'v3' ? urlencode($captcha->siteKey()) : 'explicit' }}"
        async
        defer
    ></script>
@endif

{{-- The form's two styling hooks, both set on the form itself. Every page and
     every element carries a css id and class of the admin's choosing, and
     these target them from outside. --}}
@if ($form->css_url)
    <link rel="stylesheet" href="{{ $form->css_url }}">
@endif

{{-- Last, so a rule here beats the hosted sheet — that is the point of
     having both.

     {!! !!} is deliberate: escaping would turn `>` into &gt; and every
     descendant selector with it, which breaks the one thing this field is
     for. It is safe HERE because App\Traits\Css\SanitisesCustomCss strips
     `</style` and `<script` on the way into the column, on both the create
     and the update payload, so a value that could close this block never
     reaches the database — and <style> is RAWTEXT, so with those gone
     nothing left in the value can start a tag.

     That is a guarantee about THIS context and no other. The value still
     carries whatever else the admin typed (`<img onerror=…>` survives the
     strip untouched), so it is inert in a <style> block and live XSS in a
     text node or a style="" attribute. Copy this block as-is; do not
     interpolate the column anywhere else. --}}
@if ($form->custom_css)
    <style>{!! $form->custom_css !!}</style>
@endif
