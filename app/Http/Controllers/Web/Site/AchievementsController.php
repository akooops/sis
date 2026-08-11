<?php

namespace App\Http\Controllers\Web\Site;

use App\Models\Achievement;
use App\Models\Category;
use App\Models\Page;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AchievementsController extends SiteController
{
    public function index(Request $request): View
    {
        $locale = $this->site()->locale();

        $page = Page::query()->live()->where('slug', 'achievements')->firstOrFail();

        $search = trim((string) $request->query('search')) ?: null;
        $category = trim((string) $request->query('category')) ?: null;
        $year = trim((string) $request->query('year')) ?: null;

        $achievements = Achievement::query()->live()
            ->with(['category', 'media'])
            ->when($search, fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere("title->{$locale}", 'like', "%{$search}%")))
            ->when($category, fn (Builder $query) => $query->whereHas(
                'category',
                fn (Builder $inner) => $inner->where('categories.code', $category),
            ))
            ->when($year, fn (Builder $query) => $query->whereYear('achieved_at', $year))
            ->orderByDesc('achieved_at')
            ->get();

        $achievementsByYear = $achievements->groupBy(fn (Achievement $a) => $a->achieved_at?->format('Y'))->sortKeysDesc();
        $categories = Category::query()->whereHas('achievements', fn (Builder $query) => $query->live())->orderBy('name')->get();

        $years = Achievement::query()->live()->selectRaw('YEAR(achieved_at) as year')->distinct()->orderByDesc('year')->pluck('year');

        $title = $page->getTranslation('title', $locale, true) ?: $page->name;

        $routeName = 'web.site.achievements.index';
        $routeParameters = array_filter(['search' => $search, 'category' => $category, 'year' => $year]);

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

        return view('site::pages.achievements.index', [
            'page' => $page,
            'achievements' => $achievements,
            'achievementsByYear' => $achievementsByYear,
            'categories' => $categories,
            'years' => $years,
            'search' => $search,
            'category' => $category,
            'year' => $year,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function show(Request $request): View
    {
        $locale = $this->site()->locale();

        $slug = $request->route('slug');

        $achievement = Achievement::query()->live()
            ->with(['category', 'media'])
            ->where('slug', $slug)
            ->firstOrFail();

        $sameYear = $achievement->achieved_at === null 
            ? collect()
            : Achievement::query()->live()->with('media')->whereYear('achieved_at', $achievement->achieved_at->format('Y'))->whereKeyNot($achievement->id)
                ->orderByDesc('achieved_at')->limit(6)->get();

        $title = $achievement->getTranslation('title', $locale, true) ?: $achievement->name;

        $routeName = 'web.site.achievements.show';
        $routeParameters = ['slug' => $achievement->slug];

        $seo = [
            'title' => $title,
            'description' => $achievement->getTranslation('description', $locale, true),
            'image' => $achievement->thumbnail_url,
            'canonical' => route($routeName, $routeParameters),
            'robots' => 'index,follow',
            'type' => 'article',
            'alternates' => $this->site()->languages()->mapWithKeys(fn ($language) => [
                $language->code => route($routeName, ['locale' => $language->code] + $routeParameters),
            ]),
        ];

        $breadcrumbs = [
            ['label' => __('site.breadcrumbs.achievements'), 'url' => route('web.site.achievements.index')],
            ['label' => $title, 'url' => null],
        ];

        return view('site::pages.achievements.show', [
            'achievement' => $achievement,
            'sameYear' => $sameYear,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
