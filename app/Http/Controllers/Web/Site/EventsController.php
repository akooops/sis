<?php

namespace App\Http\Controllers\Web\Site;

use App\Models\Event;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventsController extends SiteController
{
    public function index(): View
    {
        $locale = $this->site()->locale();

        $page = Page::query()->live()->where('slug', 'events')->firstOrFail();

        $events = Event::query()->live()->with('media')->orderBy('start_at')->get();
        $calendarEvents = $events->map(fn (Event $event) => [
            'title' => $event->getTranslation('title', $locale, true) ?: $event->name,
            'start' => $event->start_at?->toIso8601String(),
            'end' => $event->end_at?->toIso8601String(),
            'url' => route('web.site.events.show', ['slug' => $event->slug]),
        ])->values();

        $title = $page->getTranslation('title', $locale, true) ?: $page->name;

        $routeName = 'web.site.events.index';
        $routeParameters = [];

        $seo = [
            'title' => $title,
            'description' => $page->getTranslation('description', $locale, true),
            'image' => $page->thumbnail_url,
            'canonical' => route($routeName, $routeParameters),
            'robots' => 'index,follow',
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
