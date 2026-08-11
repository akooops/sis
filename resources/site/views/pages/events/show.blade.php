@extends('site::layout')

@section('content')
    @include('site::partials.page-hero', [
        'image' => $event->thumbnail_url,
        'title' => $event->getTranslation('title', $site->locale(), true) ?: $event->name,
    ])

    @include('site::partials.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            <ul class="post-meta mb-6">
                <li>
                    <i class="uil uil-calendar-alt" aria-hidden="true"></i>
                    <span>@lang('site.events.starts'): {{ $event->start_at?->translatedFormat('j M Y, H:i') }}</span>
                </li>
                @if ($event->end_at)
                    <li>
                        <i class="uil uil-clock" aria-hidden="true"></i>
                        <span>@lang('site.events.ends'): {{ $event->end_at->translatedFormat('j M Y, H:i') }}</span>
                    </li>
                @endif
            </ul>
        </div>
    </section>

    @include('site::partials.page-body', [
        'title' => $event->getTranslation('title', $site->locale(), true) ?: $event->name,
        'subtitle' => $event->getTranslation('description', $site->locale(), true),
        'content' => $event->getTranslation('content', $site->locale(), true),
    ])
@endsection
