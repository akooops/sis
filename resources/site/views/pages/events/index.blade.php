@extends('site::layout')

@section('content')
    @include('site::partials.content.hero', [
        'image' => $page->thumbnail_url,
        'title' => $page->getTranslation('title', $site->locale(), true) ?: $page->name,
    ])

    @include('site::partials.content.breadcrumb')

    <section>
        <div class="container pb-14 pt-6">
            {{-- data-calendar loads FullCalendar on demand — 258 kB, so it must
                 never reach a page without one. site/calendar.js reads the events
                 off data-events and the direction off <html dir>. --}}
            {{-- Prev/next are FullCalendar's OWN buttons; calendar.js turns a
                 view change into a reload with ?month=, because only this
                 month's rows were loaded. Hence no month URLs on this element —
                 the <noscript> block below is the only place they are written. --}}
            <div class="mb-10" data-calendar data-events="{{ $calendarEvents->toJson() }}"
                data-initial-date="{{ $month->toDateString() }}"></div>

            {{-- A no-JS fallback and the accessible name for the view: without it
                 a visitor with the 258 kB blocked sees an empty div. --}}
            <noscript>
                <nav class="pager mb-6" aria-label="@lang('site.events.starts')">
                    <a class="pager-link" href="{{ $previousMonth }}" rel="prev">
                        <i class="uil uil-angle-left-b" aria-hidden="true"></i>
                    </a>
                    <span class="pager-link is-active">{{ $monthLabel }}</span>
                    <a class="pager-link" href="{{ $nextMonth }}" rel="next">
                        <i class="uil uil-angle-right-b" aria-hidden="true"></i>
                    </a>
                </nav>
            </noscript>

            @if ($events->isEmpty())
                <p class="text-muted">@lang('site.events.empty')</p>
            @endif
        </div>
    </section>
@endsection
