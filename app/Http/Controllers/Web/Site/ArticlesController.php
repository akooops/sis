<?php

namespace App\Http\Controllers\Web\Site;

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticlesController extends SiteController
{
    public function index(Request $request): View
    {
        $locale = $this->site()->locale();

        $page = Page::query()->live()->where('slug', 'articles')->firstOrFail();

        $search = trim((string) $request->query('search')) ?: null;
        $category = trim((string) $request->query('category')) ?: null;

        $articles = Article::query()->live()
            ->with(['category', 'media'])
            ->when($search, fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere("title->{$locale}", 'like', "%{$search}%")
                ->orWhere("description->{$locale}", 'like', "%{$search}%")))
            ->when($category, fn (Builder $query) => $query->whereHas(
                'category',
                fn (Builder $inner) => $inner->where('categories.code', $category),
            ))
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();


        $popular = Article::query()->live()->with('media')->whereKeyNot($articles->pluck('id')->all())->inRandomOrder()->limit(6)->get();
        $categories = Category::query()->whereHas('articles', fn (Builder $query) => $query->live())->orderBy('name')->get();

        $title = $page->getTranslation('title', $locale, true) ?: $page->name;

        $routeName = 'web.site.articles.index';

        $routeParameters = array_filter([
            'search' => $search,
            'category' => $category,
            'page' => $articles->currentPage() > 1 ? $articles->currentPage() : null,
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

        return view('site::pages.articles.index', [
            'page' => $page,
            'articles' => $articles,
            'categories' => $categories,
            'search' => $search,
            'category' => $category,
            'popular' => $popular,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function show(Request $request): View
    {
        $locale = $this->site()->locale();

        $slug = $request->route('slug');

        $article = Article::query()->live()->with(['category', 'media'])->where('slug', $slug)->firstOrFail();
        $popular = Article::query()->live()->with('media')->whereKeyNot([$article->id])->inRandomOrder()->limit(6)->get();

        $title = $article->getTranslation('title', $locale, true) ?: $article->name;

        $routeName = 'web.site.articles.show';
        $routeParameters = ['slug' => $article->slug];

        $seo = [
            'title' => $title,
            'description' => $article->getTranslation('description', $locale, true),
            'image' => $article->thumbnail_url,
            'canonical' => route($routeName, $routeParameters),
            'robots' => 'index,follow',
            'type' => 'article',
            'alternates' => $this->site()->languages()->mapWithKeys(fn ($language) => [
                $language->code => route($routeName, ['locale' => $language->code] + $routeParameters),
            ]),
        ];

        $breadcrumbs = [
            ['label' => __('site.breadcrumbs.articles'), 'url' => route('web.site.articles.index')],
            ['label' => $title, 'url' => null],
        ];

        return view('site::pages.articles.show', [
            'article' => $article,
            'popular' => $popular,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
