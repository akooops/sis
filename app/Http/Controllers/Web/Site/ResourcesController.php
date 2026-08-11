<?php

namespace App\Http\Controllers\Web\Site;

use App\Models\Calendar;
use App\Models\Document;
use App\Models\Grade;
use App\Models\Newsletter;
use App\Models\Page;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
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

    public function guidelines(Request $request): View
    {
        $locale = $this->site()->locale();

        $page = Page::query()->live()->where('slug', 'guidelines')->firstOrFail();

        /*
         * ONE FLAT TABLE, not a collapsible card per grade. The whole set is a
         * handful of files; a reader looking for one document should see them all
         * at once with the grade beside each, and narrow only if they want to.
         *
         * Filtered by grade ID because `grades` has neither a slug nor a code —
         * unlike categories, whose filter is `code`. A ULID in the query string
         * is ugly but it is the only stable handle the table offers.
         */
        $grade = trim((string) $request->query('grade')) ?: null;

        // Every grade that HAS a guidelines file, for the select — offering one
        // with nothing behind it is a filter that empties the table.
        $grades = Grade::query()
            ->whereHas('media', fn (Builder $query) => $query->where('collection_name', Grade::GUIDELINES_COLLECTION))
            ->with('program')
            ->ordered()
            ->get();

        /*
         * The rows: one per FILE, flattened across grades, each carrying the
         * grade it came from. Built here rather than in the view so the template
         * is a table and nothing else.
         *
         * `guidelines` is a media COLLECTION, not a relation, so there is no
         * $grade->files to eager-load or to constrain — with('media') loads every
         * collection and getMedia() narrows it.
         */
        $files = Grade::query()
            ->when($grade, fn (Builder $query) => $query->whereKey($grade))
            ->whereHas('media', fn (Builder $query) => $query->where('collection_name', Grade::GUIDELINES_COLLECTION))
            ->with(['media', 'program'])
            ->ordered()
            ->get()
            ->flatMap(fn (Grade $row) => $row->getMedia(Grade::GUIDELINES_COLLECTION)->map(fn ($file) => [
                'name' => $file->name,
                'size' => $file->size,
                'url' => $file->url,
                'grade' => $row->getTranslation('title', $locale, true) ?: $row->name,
            ]))
            ->values();

        $title = $page->getTranslation('title', $locale, true) ?: $page->name;

        $routeName = 'web.site.guidelines';
        $routeParameters = array_filter(['grade' => $grade]);

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

        return view('site::pages.resources.guidelines', [
            'page' => $page,
            'grades' => $grades,
            'files' => $files,
            'grade' => $grade,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
