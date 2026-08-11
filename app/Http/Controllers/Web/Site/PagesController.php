<?php

namespace App\Http\Controllers\Web\Site;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The generic content page at /{locale}/{slug} — the LAST route in the site
 * group, so it only ever sees a slug no fixed route claimed.
 */
class PagesController extends SiteController
{
    public function show(Request $request): View
    {
        $locale = $this->site()->locale();

        $slug = $request->route('slug');

        $page = Page::query()->live()->where('slug', $slug)->firstOrFail();

        $title = $page->getTranslation('title', $locale, true);

        $routeName = 'web.site.pages.show';
        $routeParameters = ['slug' => $page->slug];

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

        return view('site::pages.page', [
            'page' => $page,
            'menu' => $page->menu?->loadMissing(['rootItems.linkable', 'rootItems.children.linkable']),
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
