<?php

namespace App\Http\Controllers\Web\Site;

use App\Models\Calendar;
use App\Models\Document;
use App\Models\Grade;
use App\Models\Newsletter;
use App\Models\Page;
use Illuminate\View\View;

class ResourcesController extends SiteController
{
    public function calendars(): View
    {
        $locale = $this->site()->locale();

        $page = Page::query()->live()->where('slug', 'calendars')->firstOrFail();

        $calendars = Calendar::query()->where('is_active', true)->with('media')->orderBy('start_date')->get();

        $title = $page->getTranslation('title', $locale, true) ?: $page->name;

        $routeName = 'web.site.calendars';

        $seo = [
            'title' => $title,
            'description' => $page->getTranslation('description', $locale, true),
            'image' => $page->thumbnail_url,
            'canonical' => route($routeName),
            'robots' => 'index,follow',
            'type' => 'website',
            'alternates' => $this->site()->languages()->mapWithKeys(fn ($language) => [
                $language->code => route($routeName, ['locale' => $language->code]),
            ]),
        ];

        $breadcrumbs = [['label' => $title, 'url' => null]];

        return view('site::pages.resources.calendars', [
            'page' => $page,
            'calendars' => $calendars,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function newsletters(): View
    {
        $locale = $this->site()->locale();

        $page = Page::query()->live()->where('slug', 'newsletters')->firstOrFail();

        $newsletters = Newsletter::query()->live()->with('media')->latest('published_at')->get();

        $title = $page->getTranslation('title', $locale, true) ?: $page->name;

        $routeName = 'web.site.newsletters';

        $seo = [
            'title' => $title,
            'description' => $page->getTranslation('description', $locale, true),
            'image' => $page->thumbnail_url,
            'canonical' => route($routeName),
            'robots' => 'index,follow',
            'type' => 'website',
            'alternates' => $this->site()->languages()->mapWithKeys(fn ($language) => [
                $language->code => route($routeName, ['locale' => $language->code]),
            ]),
        ];

        $breadcrumbs = [['label' => $title, 'url' => null]];

        return view('site::pages.resources.newsletters', [
            'page' => $page,
            'newsletters' => $newsletters,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function documents(): View
    {
        $locale = $this->site()->locale();

        $page = Page::query()->live()->where('slug', 'documents')->firstOrFail();

        $documents = Document::query()->with('media')->orderBy('name')->get();

        $title = $page->getTranslation('title', $locale, true) ?: $page->name;

        $routeName = 'web.site.documents';

        $seo = [
            'title' => $title,
            'description' => $page->getTranslation('description', $locale, true),
            'image' => $page->thumbnail_url,
            'canonical' => route($routeName),
            'robots' => 'index,follow',
            'type' => 'website',
            'alternates' => $this->site()->languages()->mapWithKeys(fn ($language) => [
                $language->code => route($routeName, ['locale' => $language->code]),
            ]),
        ];

        $breadcrumbs = [['label' => $title, 'url' => null]];

        return view('site::pages.resources.documents', [
            'page' => $page,
            'documents' => $documents,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function guidelines(): View
    {
        $locale = $this->site()->locale();

        $page = Page::query()->live()->where('slug', 'guidelines')->firstOrFail();

        $grades = Grade::query()->with(['media', 'program'])->ordered()->get();

        $title = $page->getTranslation('title', $locale, true) ?: $page->name;

        $routeName = 'web.site.guidelines';

        $seo = [
            'title' => $title,
            'description' => $page->getTranslation('description', $locale, true),
            'image' => $page->thumbnail_url,
            'canonical' => route($routeName),
            'robots' => 'index,follow',
            'type' => 'website',
            'alternates' => $this->site()->languages()->mapWithKeys(fn ($language) => [
                $language->code => route($routeName, ['locale' => $language->code]),
            ]),
        ];

        $breadcrumbs = [['label' => $title, 'url' => null]];

        return view('site::pages.resources.guidelines', [
            'page' => $page,
            'grades' => $grades,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
