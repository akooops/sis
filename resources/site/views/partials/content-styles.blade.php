{{--
    A content record's own stylesheet and inline CSS.

    Eight models carry `css_url` + `custom_css` — Page, Article, Album, Event,
    Achievement, JobOffer, Brand and Form — and until now only Form rendered
    them, so on every other type an admin could fill both fields in and nothing
    whatsoever happened to the page.

    Expects: $model (anything with css_url / custom_css).

    SCOPING IS THE AUTHOR'S JOB, AND #page-content IS WHAT THEY SCOPE TO. There
    is no real scoping mechanism in CSS short of shadow DOM or a build step, so
    what this gives them is a stable hook: the content presentation div carries
    `id="page-content"`, the admin hint under both fields says to write every
    rule under it, and a rule written that way cannot reach the header, the
    footer or anything else on the page. A rule written WITHOUT it still applies
    site-wide on this URL — that is a property of CSS, not a hole in this, and
    it is why the hint is worded as an instruction rather than a suggestion.

    Order matters: the hosted sheet first, the inline block second, so a rule an
    admin types here beats the file it is patching. Same order as the form
    renderer, for the same reason.
--}}
@php($model = $model ?? null)

@if ($model?->css_url)
    <link rel="stylesheet" href="{{ $model->css_url }}">
@endif

{{--
    {!! !!} is deliberate: escaping would turn `>` into &gt; and every descendant
    selector with it, which breaks the one thing this field is for.

    It is safe HERE because App\Traits\Css\SanitisesCustomCss strips `</style`
    and `<script` on the way into the column, on both the create and the update
    payload, so a value that could close this block never reaches the database —
    and <style> is RAWTEXT, so with those gone nothing left in the value can
    start a tag.

    That is a guarantee about THIS context and no other. The value still carries
    whatever else the admin typed (`<img onerror=…>` survives the strip
    untouched), so it is inert in a <style> block and live XSS in a text node or
    a style="" attribute. Copy this block as-is; do not interpolate the column
    anywhere else.
--}}
@if ($model?->custom_css)
    <style>{!! $model->custom_css !!}</style>
@endif
