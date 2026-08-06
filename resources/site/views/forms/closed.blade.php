{{-- TEMPORARY — see resources/site/views/layout.blade.php --}}
@extends('site::layout')

@section('robots', 'noindex,nofollow')
@section('title', __('forms.closed').' — '.config('app.name'))

@section('content')
    <div class="sisf">
        <h1 class="sisf-title">{{ $form->getTranslation('title', $locale, true) ?: $form->name }}</h1>
        <p class="sisf-description">{{ __('forms.closed') }}</p>
    </div>
@endsection
