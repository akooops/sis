<?php

namespace App\Http\Controllers\Web\Site;

use App\Models\Achievement;
use App\Models\Album;
use App\Models\Article;
use App\Models\Banner;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Program;
use Illuminate\View\View;

class HomeController extends SiteController
{
    public function index(): View
    {
        $locale = $this->site()->locale();

        $page = Page::query()->live()->where('slug', 'home')->firstOrFail();

        $banners = $this->site()->setting('homepage.banner_mode') === 'random'
            ? Banner::query()->live()->inRandomOrder()->limit(1)->get()
            : Banner::query()->live()->ordered()->get();

        $pathway = Program::with('streams')->find($this->site()->setting('homepage.pathway_program'));

        $programs = Program::query()->ordered()->get();

        $articles = Article::query()->live()
            ->with('category')
            ->latest('published_at')
            ->limit(12)
            ->get();

        $achievements = Achievement::query()->live()
            ->with('category')
            ->orderByDesc('achieved_at')
            ->limit(12)
            ->get();

        $achievementsByYear = $achievements
            ->groupBy(fn (Achievement $a) => $a->achieved_at?->format('Y'))
            ->sortKeysDesc()
            ->take(2);

        $albums = Album::query()->live()
            ->latest('published_at')
            ->limit(6)
            ->get();

        $partners = Partner::query()->ordered()->get();

        $routeName = 'web.site.home';
        $routeParameters = [];

        $seo = [
            'title' => $page->getTranslation('title', $locale, true) ?: $page->name,
            'description' => $page->getTranslation('description', $locale, true),
            'image' => $page->thumbnail_url,
            'canonical' => route($routeName, $routeParameters),
            'robots' => 'index,follow',
            'type' => 'website',
            'alternates' => $this->site()->languages()->mapWithKeys(fn ($language) => [
                $language->code => route($routeName, ['locale' => $language->code] + $routeParameters),
            ]),
        ];

        $breadcrumbs = [];

        return view('site::pages.home', [
            'page' => $page,
            'banners' => $banners,
            'articles' => $articles,
            'albums' => $albums,
            'achievements' => $achievements,
            'achievementsByYear' => $achievementsByYear,
            'programs' => $programs,
            'pathway' => $pathway,
            'partners' => $partners,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
