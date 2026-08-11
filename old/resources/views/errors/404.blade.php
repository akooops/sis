@php
    $page = \App\Models\Page::where('slug', 'error')->first();
@endphp

@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('page', ['slug' => $page->slug]))

@section('content')

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

        <p data-aos="fade-up" data-aos-duration="1500">
            {{ $page->getLocalTranslation('description') }}
        </p>

        <hr class="mb-4 mt-2 border-line" data-aos="fade-up" data-aos-duration="1500">

        <div class="prose w-full" data-aos="fade-up" data-aos-duration="2000">
            {!! $page->getLocalTranslation('content') !!}
        </div>

        <a class="btn mt-4" href="{{ route('index') }}">
            {{ getLanguageKeyLocalTranslation('error_page_button_title') }}
        </a>
    </div>
</section>

@endsection
