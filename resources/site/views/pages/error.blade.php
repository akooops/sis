@php
    try {
        $errorPage = \App\Models\Page::query()->live()->where('slug', 'error')->first();
    } catch (\Throwable) {
        $errorPage = null;
    }

    $title = $errorPage?->getTranslation('title', $site->locale(), true) ?: __('site.error.title');
    $body = $errorPage?->getTranslation('description', $site->locale(), true) ?: __('site.error.body');
    $content = $errorPage?->getTranslation('content', $site->locale(), true);

    $seo = [
        'title' => $title,
        'description' => $body,
        'image' => $errorPage?->thumbnail_url,
        'canonical' => url()->current(),
        'robots' => 'noindex,follow',
        'type' => 'website',
        'alternates' => [],
    ];

    $breadcrumbs = [];
@endphp

@extends('site::layout')

@section('content')
    @include('site::partials.page-hero', [
        'image' => $errorPage?->thumbnail_url,
        'title' => $title,
    ])

    <section>
        <div class="container pb-14 pt-10">
            <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand">{{ $title }}</h2>

            <hr class="mx-auto mb-8 mt-2 max-w-xl border-line">

            <p class="mb-8">{{ $body }}</p>

            @if ($content)
                <div class="prose mx-auto mb-8 text-start">{!! $content !!}</div>
            @endif

            <a class="btn" href="{{ route('web.site.home') }}">
                <i class="uil uil-angle-right-b" aria-hidden="true"></i>
                @lang('common.back_home')
            </a>
        </div>
    </section>
@endsection
