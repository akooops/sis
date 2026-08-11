<?php

namespace App\Http\Controllers\Web\Site;

use App\Models\Event;
use App\Models\Page;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class EventsController extends SiteController
{
    public function index(Request $request): View
    {
        $locale = $this->site()->locale();

        $page = Page::query()->live()->where('slug', 'events')->firstOrFail();

        $month = Carbon::hasFormat((string) $request->query('month'), 'Y-m')
            ? Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth()
            : Carbon::now()->startOfMonth();

        $monthEnd = $month->copy()->endOfMonth();

        /*
         * OVERLAPS the month rather than starting in it — an event running from
         * the 30th into the next month belongs on both calendars. An event with
         * no end_at is a single point in time.
         */
        $events = Event::query()->live()
            ->with('media')
            ->where('start_at', '<=', $monthEnd)
            ->where(fn (Builder $query) => $query
                ->where('end_at', '>=', $month)
                ->orWhere(fn (Builder $inner) => $inner
                    ->whereNull('end_at')
                    ->where('start_at', '>=', $month)))
            ->orderBy('start_at')
            ->get();

        $calendarEvents = $events->map(fn (Event $event) => [
            'title' => $event->getTranslation('title', $locale, true) ?: $event->name,
            'start' => $event->start_at?->toIso8601String(),
            'end' => $event->end_at?->toIso8601String(),
            'url' => route('web.site.events.show', ['slug' => $event->slug]),
        ])->values();

        $title = $page->getTranslation('title', $locale, true) ?: $page->name;

        $routeName = 'web.site.events.index';

        // The current month is the canonical view, so it carries no parameter.
        $routeParameters = $month->isSameMonth(Carbon::now()) ? [] : ['month' => $month->format('Y-m')];

        $seo = [
            'title' => $title,
            'description' => $page->getTranslation('description', $locale, true),
            'image' => $page->thumbnail_url,
            'canonical' => route($routeName, $routeParameters),
            'robots' => $routeParameters === [] ? 'index,follow' : 'noindex,follow',
            'type' => 'website',
            'alternates' => $this->site()->languages()->mapWithKeys(fn ($language) => [
                $language->code => route($routeName, ['locale' => $language->code] + $routeParameters),
            ]),
        ];

        $breadcrumbs = [['label' => $title, 'url' => null]];

        return view('site::pages.events.index', [
            'page' => $page,
            'events' => $events,
            'calendarEvents' => $calendarEvents,
            'month' => $month,
            'monthLabel' => $month->translatedFormat('F Y'),
            'previousMonth' => route($routeName, ['month' => $month->copy()->subMonth()->format('Y-m')]),
            'nextMonth' => route($routeName, ['month' => $month->copy()->addMonth()->format('Y-m')]),
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function show(Request $request): View
    {
        $locale = $this->site()->locale();

        $slug = $request->route('slug');

        $event = Event::query()->live()->with('media')->where('slug', $slug)->firstOrFail();

        $title = $event->getTranslation('title', $locale, true) ?: $event->name;

        $routeName = 'web.site.events.show';
        $routeParameters = ['slug' => $event->slug];

        $seo = [
            'title' => $title,
            'description' => $event->getTranslation('description', $locale, true),
            'image' => $event->thumbnail_url,
            'canonical' => route($routeName, $routeParameters),
            'robots' => 'index,follow',
            'type' => 'website',
            'alternates' => $this->site()->languages()->mapWithKeys(fn ($language) => [
                $language->code => route($routeName, ['locale' => $language->code] + $routeParameters),
            ]),
        ];

        $breadcrumbs = [
            ['label' => __('site.breadcrumbs.events'), 'url' => route('web.site.events.index')],
            ['label' => $title, 'url' => null],
        ];

        return view('site::pages.events.show', [
            'event' => $event,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
