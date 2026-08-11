@extends('layouts.master')
@section('title', $album->getLocalTranslation('title'))
@section('description', $album->getLocalTranslation('description'))
@section('canonical', route('album', ['slug' => $album->slug]))
@section('image', $album->thumbnailUrl)

@section('content')

@php
    $photos = $album->files->reject(fn ($file) => str_starts_with($file->type, 'video/'));
    $videos = $album->files->filter(fn ($file) => str_starts_with($file->type, 'video/'));
@endphp

@include('partials.page-hero', [
    'image' => $album->thumbnailUrl,
    'title' => $album->getLocalTranslation('title'),
])

@include('partials.breadcrumb', [
    'links' => [[
        'label' => getLanguageKeyLocalTranslation('breadcrumbs_albums_page_title'),
        'url' => route('albums'),
    ]],
    'current' => $album->getLocalTranslation('title'),
])

<section>
    <div class="container pb-14 pt-6">
        <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand"
            data-aos="fade-up" data-aos-duration="1000">
            {{ $album->getLocalTranslation('title') }}
        </h2>

        <hr class="mb-4 mt-2 border-line" data-aos="fade-up" data-aos-duration="1500">

        @if ($photos->isNotEmpty())
            <div class="mt-5 grid gap-6 sm:grid-cols-2 md:grid-cols-3" data-aos="fade-up" data-aos-duration="2000">
                @foreach ($photos as $file)
                    <figure class="overlay cursor-zoom-in rounded-md">
                        <a href="{{ $file->url }}" data-lightbox data-gallery="album-{{ $album->id }}">
                            <img class="h-[250px] w-full rounded-md object-cover" src="{{ $file->url }}"
                                alt="{{ $album->getLocalTranslation('title') }}">
                        </a>
                    </figure>
                @endforeach
            </div>
        @endif

        @if ($videos->isNotEmpty())
            <hr class="mb-4 mt-8 border-line">

            <h2 class="mb-4 text-6xl font-semibold uppercase leading-[42px] text-brand">
                {{ getLanguageKeyLocalTranslation('albums_videos_title') }}
            </h2>

            <div class="mt-5 grid gap-6 sm:grid-cols-2 md:grid-cols-3" data-aos="fade-up" data-aos-duration="2000">
                @foreach ($videos as $file)
                    <figure class="rounded-md">
                        <video class="h-[250px] w-full rounded-md object-cover" controls preload="metadata">
                            <source src="{{ $file->url }}" type="{{ $file->type }}">
                        </video>
                    </figure>
                @endforeach
            </div>
        @endif
    </div>
</section>

@endsection
