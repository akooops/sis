<?php

namespace App\Http\Controllers\Web\Site;

use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramsController extends SiteController
{
    public function show(Request $request): View
    {
        $locale = $this->site()->locale();

        $slug = $request->route('slug');

        $program = Program::query()
            ->with(['streams' => fn ($query) => $query->ordered(), 'grades' => fn ($query) => $query->ordered(), 'media'])
            ->where('slug', $slug)
            ->firstOrFail();

        $requested = trim((string) $request->query('stream')) ?: null;
        $activeStream = $program->streams->firstWhere('slug', $requested) ?? $program->streams->first();

        $title = $program->getTranslation('title', $locale, true) ?: $program->name;

        $routeName = 'web.site.programs.show';
        $routeParameters = array_filter(['slug' => $program->slug, 'stream' => $requested]);

        $seo = [
            'title' => $title,
            'description' => $program->getTranslation('subtitle', $locale, true)
                ?: $program->getTranslation('description', $locale, true),
            'image' => $program->thumbnail_url,
            'canonical' => route($routeName, $routeParameters),
            'robots' => $requested === null ? 'index,follow' : 'noindex,follow',
            'type' => 'website',
            'alternates' => $this->site()->languages()->mapWithKeys(fn ($language) => [
                $language->code => route($routeName, ['locale' => $language->code] + $routeParameters),
            ]),
        ];

        $breadcrumbs = [
            ['label' => __('site.breadcrumbs.programs'), 'url' => null],
            ['label' => $title, 'url' => null],
        ];

        return view('site::pages.programs.show', [
            'program' => $program,
            'activeStream' => $activeStream,
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
