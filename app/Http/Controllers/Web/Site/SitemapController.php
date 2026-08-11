<?php

namespace App\Http\Controllers\Web\Site;

use App\Models\Achievement;
use App\Models\Album;
use App\Models\Article;
use App\Models\Brand;
use App\Models\Event;
use App\Models\Form;
use App\Models\JobOffer;
use App\Models\Language;
use App\Models\Page;
use App\Models\Program;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

/**
 * sitemap.xml and robots.txt.
 *
 * ONE sitemap, not one per locale. Every URL is listed once in the default
 * locale with an <xhtml:link> alternate per enabled locale — which is what
 * search engines want, and it means a new locale does not mint a new document
 * nobody has submitted.
 */
class SitemapController extends SiteController
{
    /**
     * Which model feeds which route.
     *
     * Every entry needs a slug and a public detail route, which is exactly the
     * set of models that have both — Grade, Calendar, Newsletter and Document
     * have no slug and therefore no URL to list.
     *
     * @var array<string, array{model: class-string, route: string, scope: string}>
     */
    /**
     * A system Page's slug => the FIXED route that renders it.
     *
     * These slugs never reach the generic /{locale}/{slug} route: their routes
     * are declared above it and win, so /articles is the listing and never a
     * Page lookup. The map lives here because this is the only thing that needs
     * it — the sitemap has to emit each of these once, at its real route, and
     * must not also emit it as a generic page. `home` has no listing to link and
     * `error` has no route at all.
     *
     * @var array<string, string>
     */
    protected const SLUG_ROUTES = [
        'home' => 'web.site.home',
        'articles' => 'web.site.articles.index',
        'albums' => 'web.site.albums.index',
        'events' => 'web.site.events.index',
        'achievements' => 'web.site.achievements.index',
        'identity' => 'web.site.brands.index',
        'jobs' => 'web.site.jobs.index',
        'calendars' => 'web.site.calendars',
        'newsletters' => 'web.site.newsletters',
        'guidelines' => 'web.site.guidelines',
        'documents' => 'web.site.documents',
        'contact' => 'web.site.contact',
        'inquiries' => 'web.site.inquiries',
    ];

    /**
     * Slugs with no public URL to advertise.
     *
     * `error` is the seeded 404 Page — it renders through the exception handler
     * and has no route, so it must not be listed as a generic page either.
     *
     * @var array<int, string>
     */
    protected const UNLISTED_SLUGS = ['error'];

    protected const RESOURCES = [
        'articles' => ['model' => Article::class, 'route' => 'web.site.articles.show', 'scope' => 'live'],
        'albums' => ['model' => Album::class, 'route' => 'web.site.albums.show', 'scope' => 'live'],
        'events' => ['model' => Event::class, 'route' => 'web.site.events.show', 'scope' => 'live'],
        'achievements' => ['model' => Achievement::class, 'route' => 'web.site.achievements.show', 'scope' => 'live'],
        'brands' => ['model' => Brand::class, 'route' => 'web.site.brands.show', 'scope' => 'live'],
        // Open, not live: a vacancy past its deadline should stop being offered.
        'jobs' => ['model' => JobOffer::class, 'route' => 'web.site.jobs.show', 'scope' => 'open'],
    ];

    public function index(): Response
    {
        $locales = Language::query()->enabled()->orderBy('code')->pluck('code')->all();
        $default = Language::defaultCode();

        $urls = [];

        // Home, then every listing that owns a fixed route.
        foreach (array_unique(array_values(self::SLUG_ROUTES)) as $route) {
            $urls[] = $this->entry($route, [], $default, $locales);
        }

        /*
         * Generic pages, MINUS the ones whose slug owns a fixed route. Those
         * were emitted above at their real URL, and the generic route never
         * matches them anyway — it is declared last, so /articles is the listing
         * and never a Page lookup. Listing both would advertise the same page at
         * two addresses.
         */
        Page::query()->live()
            ->whereNotIn('slug', array_merge(array_keys(self::SLUG_ROUTES), self::UNLISTED_SLUGS))
            ->select(['slug', 'updated_at'])
            ->cursor()
            ->each(function (Page $page) use (&$urls, $default, $locales) {
                $urls[] = $this->entry('web.site.pages.show', ['slug' => $page->slug], $default, $locales, $page->updated_at);
            });

        foreach (self::RESOURCES as $resource) {
            /** @var Builder $query */
            $query = $resource['model']::query()->{$resource['scope']}();

            // cursor() rather than get(): a school with ten years of articles
            // should not load them all into memory to print their slugs.
            $query->select(['slug', 'updated_at'])->cursor()->each(
                function ($record) use (&$urls, $resource, $default, $locales) {
                    $urls[] = $this->entry($resource['route'], ['slug' => $record->slug], $default, $locales, $record->updated_at);
                },
            );
        }

        // Programmes have no status column — an unwanted one is deleted.
        Program::query()->select(['slug', 'updated_at'])->cursor()->each(
            function (Program $program) use (&$urls, $default, $locales) {
                $urls[] = $this->entry('web.site.programs.show', ['slug' => $program->slug], $default, $locales, $program->updated_at);
            },
        );

        // Public forms keep their own URL shape — /forms/{locale}/{slug}.
        Form::query()->live()->select(['slug', 'updated_at'])->cursor()->each(
            function (Form $form) use (&$urls, $default, $locales) {
                $urls[] = $this->entry('web.user.forms.show', ['slug' => $form->slug], $default, $locales, $form->updated_at);
            },
        );

        return response()
            ->view('site::sitemap', ['urls' => array_filter($urls)])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * robots.txt as a route, replacing the static public/robots.txt.
     *
     * The static file HAD to be deleted: the web server serves public/ before
     * Laravel sees the request, so leaving it would make this dead code. It is a
     * route at all because the Sitemap: directive needs an absolute URL, and
     * only a route can interpolate the current environment's host.
     */
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin/',
            // The form pages are reachable and indexable through the site; their
            // /forms/ URLs are the same content at a second address, and the
            // thanks/blocked/closed pages under them are noindex anyway.
            'Disallow: /forms/',
            '',
            'Sitemap: '.route('web.site.sitemap'),
            '',
        ];

        return response(implode("\n", $lines))->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * One <url>: the default-locale address, plus an alternate per locale.
     *
     * @param  array<string, mixed>  $parameters
     * @param  array<int, string>  $locales
     * @return array<string, mixed>|null
     */
    protected function entry(string $route, array $parameters, string $default, array $locales, $lastmod = null): ?array
    {
        $alternates = [];

        foreach ($locales as $code) {
            $alternates[$code] = route($route, ['locale' => $code] + $parameters);
        }

        if (! isset($alternates[$default])) {
            return null;
        }

        return [
            'loc' => $alternates[$default],
            'lastmod' => $lastmod?->toAtomString(),
            'alternates' => $alternates + ['x-default' => $alternates[$default]],
        ];
    }
}
