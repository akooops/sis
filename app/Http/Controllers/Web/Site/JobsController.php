<?php

namespace App\Http\Controllers\Web\Site;

use App\Models\Country;
use App\Models\JobOffer;
use App\Models\Page;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobsController extends SiteController
{
    public function index(Request $request): View
    {
        $locale = $this->site()->locale();

        $page = Page::query()->live()->where('slug', 'jobs')->firstOrFail();

        $search = trim((string) $request->query('search')) ?: null;

        $jobs = JobOffer::query()->open()
            ->with(['category', 'media'])
            ->when($search, fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere("title->{$locale}", 'like', "%{$search}%")))
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        $title = $page->getTranslation('title', $locale, true) ?: $page->name;

        $routeName = 'web.site.jobs.index';
        $routeParameters = array_filter([
            'search' => $search,
            'page' => $jobs->currentPage() > 1 ? $jobs->currentPage() : null,
        ]);

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

        return view('site::pages.jobs.index', [
            'page' => $page,
            'jobs' => $jobs,
            'search' => $search,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function show(Request $request): View
    {
        // By name, not as an argument — see PagesController::show().
        $slug = $request->route('slug');

        $locale = $this->site()->locale();

        $job = JobOffer::query()->live()->with(['category', 'media'])->where('slug', $slug)->firstOrFail();

        $title = $job->getTranslation('title', $locale, true) ?: $job->name;

        $routeName = 'web.site.jobs.show';
        $routeParameters = ['slug' => $job->slug];

        $seo = [
            'title' => $title,
            'description' => $job->getTranslation('description', $locale, true),
            'image' => $job->thumbnail_url,
            'canonical' => route($routeName, $routeParameters),
            'robots' => 'index,follow',
            'type' => 'website',
            'alternates' => $this->site()->languages()->mapWithKeys(fn ($language) => [
                $language->code => route($routeName, ['locale' => $language->code] + $routeParameters),
            ]),
        ];

        $breadcrumbs = [
            ['label' => __('site.breadcrumbs.jobs'), 'url' => route('web.site.jobs.index')],
            ['label' => $title, 'url' => null],
        ];

        return view('site::pages.jobs.show', [
            'job' => $job,
            // `skills` is a ;;;-joined translatable STRING on this model, not an
            // array — splitSkills is the only correct reader.
            'skills' => JobOffer::splitSkills($job->skills),
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * The application wizard.
     *
     * INERT. The island renders and validates client-side, but there is no
     * JobApplication model and no POST route to send it to. Standing it up is a
     * backend plus two routes; the front end is already here so that work is not
     * also a translation project.
     *
     * The page is reachable but linked from nothing.
     */
    public function apply(): View
    {
        $title = __('jobs.apply.title');

        $routeName = 'web.site.jobs.apply';
        $routeParameters = [];

        $seo = [
            'title' => $title,
            'description' => null,
            'image' => null,
            'canonical' => route($routeName, $routeParameters),
            // Nothing here can be submitted yet, so it must not be indexed.
            'robots' => 'noindex,nofollow',
            'type' => 'website',
            'alternates' => $this->site()->languages()->mapWithKeys(fn ($language) => [
                $language->code => route($routeName, ['locale' => $language->code] + $routeParameters),
            ]),
        ];

        $breadcrumbs = [
            ['label' => __('site.breadcrumbs.jobs'), 'url' => route('web.site.jobs.index')],
            ['label' => $title, 'url' => null],
        ];

        return view('site::pages.jobs.apply', [
            'job' => null,
            'countries' => Country::query()->where('is_enabled', true)->orderBy('name')->get(),
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
