@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('events'))

@section('content')

@php
    $calendarEvents = $events->map(fn ($event) => [
        'title' => $event->getLocalTranslation('title'),
        'start' => $event->starts_at,
        'end' => $event->ends_at,
        'url' => route('event', ['slug' => $event->slug]),
    ])->values();
@endphp

@include('partials.page-hero', [
    'image' => $page->thumbnailUrl,
    'title' => $page->getLocalTranslation('title'),
])

@include('partials.page-menu', ['menu' => $page->menu])

@include('partials.breadcrumb', [
    'links' => [[
        'label' => getLanguageKeyLocalTranslation('breadcrumbs_index_page_title'),
        'url' => route('index'),
    ]],
    'current' => $page->getLocalTranslation('title'),
])

<section>
    <div class="container pb-14 pt-6">
        <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand"
            data-aos="fade-up" data-aos-duration="1000">
            {{ $page->getLocalTranslation('title') }}
        </h2>

        <hr class="mb-8 mt-2 border-line" data-aos="fade-up" data-aos-duration="1500">

        <div class="prose w-full" data-aos="fade-up" data-aos-duration="2000">
            {!! $page->getLocalTranslation('content') !!}
        </div>

        <div class="mt-8" data-aos="fade-up" data-aos-duration="2000"
            data-calendar data-events="{{ $calendarEvents->toJson() }}"></div>
    </div>
</section>

@endsection
