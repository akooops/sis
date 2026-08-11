@extends('layouts.master')
@section('title', $page->getLocalTranslation('title'))
@section('description', $page->getLocalTranslation('description'))
@section('canonical', route('albums'))

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
    <div class="container pb-14 pt-6" data-aos="fade-up" data-aos-duration="1000">
        <div class="mb-8 grid gap-6 sm:grid-cols-2 md:grid-cols-3">
            @foreach ($albums as $album)
                <a class="card media-card media-card-cover overlay shadow-float"
                    href="{{ route('album', ['slug' => $album->slug]) }}">
                    <img src="{{ $album->thumbnailUrl }}" alt="{{ $album->getLocalTranslation('title') }}" />
                </a>
            @endforeach
        </div>

        @include('partials.pagination', ['pagination' => $pagination, 'route' => 'albums'])
    </div>
</section>

@endsection
