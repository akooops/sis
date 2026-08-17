{{--
    What a form actually shows: its confirmation, the reason it cannot be filled
    in, or the form itself.

    ONE COPY OF THAT DECISION. The form's own page (site::pages.forms.show) and
    the site's contact and inquiries pages all include this, so a form behaves
    identically wherever it is read — and so the honeypot, the captcha and the
    telemetry beacon can never be present on one and quietly missing from
    another.

    THERE IS NO THANKS PAGE, CLOSED PAGE OR BLOCKED PAGE. Each of those used to
    be its own URL carrying the site's chrome and one sentence. All three are
    alerts here, on the page the visitor is already reading.

    Expects:
      $form          ?App\Models\Form
      $presentation  ?App\Services\Forms\FormPresentation — null whenever there
                     is no form to draw, for any reason
      $notice        ?string 'blocked'|'closed' — why there is none, when that is
                     something the visitor should be told. Null when submit()
                     already put the same sentence in the error bag.
      $chrome        ?bool — forwarded to site::partials.form-renderer
      $presets       ?array — answers the page supplies, forwarded to the renderer
--}}
@php
    $notice = $notice ?? null;
    $presentation = $presentation ?? null;
    $form = $form ?? null;

    /*
     * THE FLASH IS THE GATE, and that is what makes the confirmation honest.
     *
     * A flash is consumed, so a refresh or a back-navigation cannot show the
     * confirmation twice or count a second conversion — which the old public
     * /thanks GET, reachable by anyone who typed the URL, could not promise.
     *
     * Keyed by form id: a page may embed more than one form, and only the one
     * that was actually submitted may confirm.
     */
    $submitted = $form !== null && session('sisf_submitted') === $form->id;

@endphp

@if ($submitted)
    {{--
        NO data-sisf here. It used to carry the marker so site.js would download
        the form module on this render and report a conversion — the form emits
        no analytics of its own any more, so a confirmation needs no JavaScript
        at all.
    --}}
    <div data-aos="fade-up" data-aos-duration="1000">
        <div class="alert alert-success" role="alert">
            {{-- Admin-authored, same trust level as any other page content. --}}
            <div class="[&>*+*]:mt-2">
                {!! $form->getTranslation('confirmation_message', $site->locale(), true) !!}
            </div>
        </div>
    </div>
@elseif ($notice)
    {{--
        Deliberately vague about WHICH rule fired: a blocked visitor learning
        whether it was their country or their address is a visitor learning how
        to get around it.

        No data-auto-dismiss anywhere in this file. site/disclosure.js removes
        anything carrying it after five seconds, which is right for a transient
        flash and wrong for a message the visitor is meant to read — and for a
        confirmation whose reference is the only copy they get.
    --}}
    <div class="alert alert-{{ $notice === 'blocked' ? 'danger' : 'warning' }} flex items-center gap-2"
        role="alert" data-aos="fade-up" data-aos-duration="1000">
        <i class="uil uil-exclamation-triangle" aria-hidden="true"></i>
        <span class="flex-1">{{ $notice === 'blocked' ? __('forms.blocked') : __('forms.closed') }}</span>
    </div>
@elseif ($presentation)
    {{-- data-sisf ONLY — no `sisf` class. FormRenderer emits its own `.sisf`
         root inside [data-sisf-root], and nesting one inside another applies
         the token block and its padding twice. --}}
    <div data-sisf data-aos="fade-up" data-aos-duration="1000">
        @include('site::partials.form-renderer', ['presets' => $presets ?? [], 'chooser' => $chooser ?? false])
    </div>
@endif
