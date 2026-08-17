@extends('site::layout')

@section('content')
    @include('site::partials.content.hero', [
        'image' => $page->thumbnail_url,
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
    ])

    @include('site::partials.content.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            @if ($albums->isEmpty())
                <p class="text-muted">@lang('site.albums.empty')</p>
            @else
                <div class="grid auto-rows-fr gap-6 md:grid-cols-2 lg:grid-cols-3 lg:gap-x-8">
                    @foreach($albums as $album)
                    <article class="card media-card" data-aos="fade-up" data-aos-duration="1000">
                        <figure class="overlay h-[450px]">
                            <a href="{{ route('web.site.albums.show', ['slug' => $album->slug]) }}">
                                <img class="h-full w-full object-cover" src="{{ $album->thumbnail_url }}"
                                    alt="{{ $album->getTranslation('title', $site->locale(), true) }}"
                                    loading="lazy">
                            </a>
                        </figure>
                    </article>
                    @endforeach
                </div>

                <div class="mt-8">
                    @include('site::partials.ui.pagination', ['paginator' => $albums])
                </div>
            @endif
        </div>
    </section>
@endsection
