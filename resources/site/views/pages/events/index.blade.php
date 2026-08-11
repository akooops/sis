@extends('site::layout')

@section('content')
    @include('site::partials.page-hero', [
        'image' => $page->thumbnail_url,
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
    ])

    @include('site::partials.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            @if ($events->isEmpty())
                <p class="text-muted">@lang('site.events.empty')</p>
            @else
                <div class="mb-10" data-calendar data-events="{{ $calendarEvents->toJson() }}"></div>

                <div class="grid auto-rows-fr gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($events as $event)
                        @php($url = route('web.site.events.show', ['slug' => $event->slug]))

                        <article class="card media-card">
                            @if ($event->thumbnail_url)
                                <figure class="overlay media-figure">
                                    <a href="{{ $url }}">
                                        <img src="{{ $event->thumbnail_url }}"
                                            alt="{{ $event->getTranslation('title', $site->locale(), true) }}"
                                            loading="lazy">
                                    </a>
                                </figure>
                            @endif

                            <div class="card-body">
                                <h2 class="mb-2 text-3xl font-black uppercase leading-[35px] text-brand">
                                    <a class="hover:text-brand-soft" href="{{ $url }}">
                                        {{ $event->getTranslation('title', $site->locale(), true) ?: $event->name }}
                                    </a>
                                </h2>

                                <p class="mb-0">{{ $event->getTranslation('description', $site->locale(), true) }}</p>
                            </div>

                            <div class="card-footer">
                                <ul class="post-meta">
                                    <li>
                                        <i class="uil uil-calendar-alt" aria-hidden="true"></i>
                                        <span>{{ $event->start_at?->translatedFormat('j M Y') }}</span>
                                    </li>
                                </ul>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
