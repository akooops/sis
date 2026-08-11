{{--
    The public form renderer's mount point and its payload.

    ONE COPY OF THE PAYLOAD CONTRACT. Both the standalone form page
    (/forms/{locale}/{slug}) and the site's own contact and admissions pages
    include this, so the schema shape, the token, the honeypot and the captcha
    wiring cannot drift between them.

    Expects: $presentation (App\Services\Forms\FormPresentation).
--}}
@php
    $form = $presentation->form;
    $captcha = $presentation->captcha;
@endphp

@if ($errors->any())
    <div class="sisf-alert" role="alert">
        <ul>
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
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
        'telemetryAction' => $presentation->telemetryAction,
        // What the page is allowed to measure. Sent rather than compiled in,
        // so switching a capture off in .env switches off the LISTENER, not
        // just the column — nothing is collected that is not sent.
        'capture' => config('forms.capture'),
        'honeypot' => $presentation->honeypot,
        'old' => (object) old('fields', []),
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

{{-- The funnel marker only — the layout already loaded the site's tag. --}}
@include('site::forms.partials.tracking', ['form' => $form, 'stage' => 'form'])

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
