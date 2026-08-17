@extends('site::layout')

@section('content')
    @include('site::partials.content.hero', [
        'image' => $album->thumbnail_url,
        'title' => $album->getTranslation('title', $site->locale(), true) ?: $album->name,
    ])

    @include('site::partials.content.breadcrumb')

    @include('site::partials.content.body', [
        'title' => $album->getTranslation('title', $site->locale(), true) ?: $album->name,
        'subtitle' => $album->getTranslation('description', $site->locale(), true),
        'content' => $album->getTranslation('content', $site->locale(), true),
        'styles' => $album,
    ])

    @if ($files->isNotEmpty())
        <section>
            <div class="container pb-14">
                {{-- data-lightbox marks the CONTAINER: it is what site.js gates
                     the GLightbox import on, and what scopes the plugin to the
                     anchors inside. data-gallery groups them into one set. --}}
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-lightbox>
                    @foreach ($files as $file)
                        {{-- A gallery holds images AND video. An <img> pointed at
                             an .mp4 renders as a broken-image icon with the
                             filename beside it, which is what this tile used to
                             be; a <video preload="metadata"> paints its FIRST
                             FRAME instead, so the poster costs no server-side
                             thumbnail generation and no extra column.

                             data-type tells GLightbox to open it as a player
                             rather than trying to load it as an image. --}}
                        @php($isVideo = str_starts_with((string) $file->mime_type, 'video/'))

                        <a class="overlay media-figure block h-[260px] overflow-hidden rounded-md"
                            href="{{ $file->url }}" data-gallery="album-{{ $album->id }}"
                            data-title="{{ $file->name }}" @if ($isVideo) data-type="video" @endif>

                            @if ($isVideo)
                                {{-- muted + playsinline so a browser will paint the
                                     frame without asking; no controls, because the
                                     tile is a link and the lightbox is the player. --}}
                                <video class="h-full w-full object-cover" preload="metadata" muted playsinline
                                    tabindex="-1" aria-label="{{ $file->name }}">
                                    <source src="{{ $file->url }}" type="{{ $file->mime_type }}">
                                </video>

                                <span class="media-play" aria-hidden="true">
                                    <i class="uil uil-play"></i>
                                </span>
                            @else
                                <img class="h-full w-full object-cover" src="{{ $file->url }}" alt="{{ $file->name }}"
                                    loading="lazy">
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
