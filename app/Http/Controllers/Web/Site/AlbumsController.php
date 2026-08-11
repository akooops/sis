<?php

namespace App\Http\Controllers\Web\Site;

use App\Models\Album;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlbumsController extends SiteController
{
    public function index(): View
    {
        $locale = $this->site()->locale();

        $page = Page::query()->live()->where('slug', 'albums')->firstOrFail();

        $albums = Album::query()->live()
            ->with('media')
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        $title = $page->getTranslation('title', $locale, true) ?: $page->name;

        $routeName = 'web.site.albums.index';
        $routeParameters = array_filter([
            'page' => $albums->currentPage() > 1 ? $albums->currentPage() : null,
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

        return view('site::pages.albums.index', [
            'page' => $page,
            'albums' => $albums,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function show(Request $request): View
    {
        $slug = $request->route('slug');

        $locale = $this->site()->locale();

        $album = Album::query()->live()->with('media')->where('slug', $slug)->firstOrFail();
        $files = $album->getMedia(Album::FILES_COLLECTION);

        $title = $album->getTranslation('title', $locale, true) ?: $album->name;

        $routeName = 'web.site.albums.show';
        $routeParameters = ['slug' => $album->slug];

        $seo = [
            'title' => $title,
            'description' => $album->getTranslation('description', $locale, true),
            'image' => $album->thumbnail_url,
            'canonical' => route($routeName, $routeParameters),
            'robots' => 'index,follow',
            'type' => 'website',
            'alternates' => $this->site()->languages()->mapWithKeys(fn ($language) => [
                $language->code => route($routeName, ['locale' => $language->code] + $routeParameters),
            ]),
        ];

        $breadcrumbs = [
            ['label' => __('site.breadcrumbs.albums'), 'url' => route('web.site.albums.index')],
            ['label' => $title, 'url' => null],
        ];

        return view('site::pages.albums.show', [
            'album' => $album,
            'files' => $files,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
