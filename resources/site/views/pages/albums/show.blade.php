@extends('site::layout')

@section('content')
    @include('site::partials.page-hero', [
        'image' => $album->thumbnail_url,
        'title' => $album->getTranslation('title', $site->locale(), true) ?: $album->name,
    ])

    @include('site::partials.breadcrumb')

    @include('site::partials.page-body', [
        'title' => $album->getTranslation('title', $site->locale(), true) ?: $album->name,
        'subtitle' => $album->getTranslation('description', $site->locale(), true),
        'content' => $album->getTranslation('content', $site->locale(), true),
    ])

    @if ($files->isNotEmpty())
        <section>
            <div class="container pb-14">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-lightbox>
                    @foreach ($files as $file)
                        <a class="overlay media-figure block h-[260px] overflow-hidden rounded-md"
                            href="{{ $file->url }}" data-gallery="album-{{ $album->id }}"
                            data-title="{{ $file->name }}">
                            <img class="h-full w-full object-cover" src="{{ $file->url }}" alt="{{ $file->name }}"
                                loading="lazy">
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
