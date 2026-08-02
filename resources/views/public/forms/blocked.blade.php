{{-- TEMPORARY — see resources/views/public/layout.blade.php --}}
@extends('public.layout')

@section('robots', 'noindex,nofollow')
@section('title', __('forms.blocked').' — '.config('app.name'))

@section('content')
    <div class="sisf">
        <h1 class="sisf-title">{{ $form->getTranslation('title', $locale, true) ?: $form->name }}</h1>
        {{-- Deliberately vague about WHY: naming the exact rule tells someone
             evading it precisely what to change. --}}
        <p class="sisf-description">{{ __('forms.blocked') }}</p>
    </div>
@endsection
