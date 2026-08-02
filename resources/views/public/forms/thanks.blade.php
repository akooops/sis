{{-- TEMPORARY — see resources/views/public/layout.blade.php --}}
@extends('public.layout')

@section('robots', 'noindex,nofollow')
@section('title', __('forms.thanks_title').' — '.config('app.name'))

@section('content')
    <div class="sisf">
        <h1 class="sisf-title">{{ __('forms.thanks_title') }}</h1>

        {{-- Admin-authored, same trust level as any other page content. --}}
        <div class="sisf-content">
            {!! $form->getTranslation('confirmation_message', $locale, true) !!}
        </div>

        {{-- The submission's ULID: it is the reference, there is no second code. --}}
        @if ($reference)
            <p class="sisf-help">{{ __('forms.thanks_reference', ['reference' => $reference]) }}</p>
        @endif
    </div>

    {{--
        sisf_form_complete is reported from here, but ONLY on the request that
        actually follows a submission.

        This route is an ungated public GET, so a refresh, a back-navigation or
        anyone simply opening the URL would otherwise each count as a conversion
        — inflating the one number this whole feature exists to produce.
        `$reference` comes from a flash the controller sets only when a
        submission was persisted, so it is the honest signal.
    --}}
    @include('public.forms.partials.analytics', ['stage' => $reference ? 'complete' : 'form'])
@endsection
