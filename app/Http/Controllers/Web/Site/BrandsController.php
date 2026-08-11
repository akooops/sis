<?php

namespace App\Http\Controllers\Web\Site;

use App\Models\Brand;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandsController extends SiteController
{
    public function index(): View
    {
        $locale = $this->site()->locale();

        $page = Page::query()->live()->where('slug', 'identity')->firstOrFail();

        $brands = Brand::query()->live()->with('media')->orderBy('name')->get();

        $title = $page->getTranslation('title', $locale, true) ?: $page->name;

        $routeName = 'web.site.brands.index';
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

        return view('site::pages.brands.index', [
            'page' => $page,
            'brands' => $brands,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function show(Request $request): View
    {
        $locale = $this->site()->locale();
        
        $slug = $request->route('slug');

        $brand = Brand::query()->live()
            ->with([
                'assetGroups' => fn ($query) => $query->orderBy('order'),
                'assetGroups.assets' => fn ($query) => $query->orderBy('order'),
                'assetGroups.assets.media',
                'media',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        $title = $brand->getTranslation('title', $locale, true) ?: $brand->name;

        $routeName = 'web.site.brands.show';
        $routeParameters = ['slug' => $brand->slug];

        $seo = [
            'title' => $title,
            'description' => $brand->getTranslation('description', $locale, true),
            'image' => $brand->thumbnail_url,
            'canonical' => route($routeName, $routeParameters),
            'robots' => 'index,follow',
            'type' => 'website',
            'alternates' => $this->site()->languages()->mapWithKeys(fn ($language) => [
                $language->code => route($routeName, ['locale' => $language->code] + $routeParameters),
            ]),
        ];

        $breadcrumbs = [
            ['label' => __('site.breadcrumbs.identity'), 'url' => route('web.site.brands.index')],
            ['label' => $title, 'url' => null],
        ];

        return view('site::pages.brands.show', [
            'brand' => $brand,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
