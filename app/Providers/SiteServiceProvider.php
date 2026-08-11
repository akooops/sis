<?php

namespace App\Providers;

use App\Models\Language;
use App\Services\Site\SiteContext;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;
use Throwable;

/**
 * The public site's wiring. Nothing here touches the admin.
 */
class SiteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
         * A singleton because it is per-request state, and the container is
         * already request-scoped — so this IS the memoisation. No static
         * properties and no `Cache::` layer (this app has none by design).
         */
        $this->app->singleton(SiteContext::class, fn () => SiteContext::make());
    }

    public function boot(): void
    {
        /*
         * ONE name every site view can rely on, for the GLOBAL half of the
         * request only — chrome, languages, settings, menus.
         *
         * A page's own data does not come through here: its controller hands the
         * view everything it renders, including `$seo` and `$breadcrumbs`. That
         * is the point of the split — one method to read per page.
         *
         * One pattern, because the 404 page is `site::pages.error` and not an
         * `errors/` file — see App\Exceptions\Handler for why that placement is
         * load-bearing rather than a preference.
         */
        View::composer('site::*', function (ViewInstance $view) {
            $view->with('site', $this->app->make(SiteContext::class));
        });

        $this->hideDefaultLocaleFromUrls();
    }

    /**
     * Keep `/en` out of every URL this app generates.
     *
     * The site routes are registered TWICE — once behind `{locale}` and once
     * bare — so `/contact` and `/en/contact` both answer. Every site route still
     * carries a {locale} placeholder, so route() cannot BUILD one without a
     * value; SetLocale supplies the current locale through URL::defaults, and
     * this strips it back off again when it is the default language's.
     *
     * WITHOUT IT THE TWO REGISTRATIONS FIGHT EACH OTHER. A visitor reading
     * `/contact` would click a menu link built by route(), land on `/en/contact`,
     * and be reading the same page at a second address — duplicate content that
     * the canonical, being generated the same way, would point at rather than
     * away from. With it, the canonical, the hreflang set, the sitemap and every
     * link all say `/contact`, and `/en/contact` survives as an alias.
     *
     * It only ever strips a WHOLE first segment, so `/admin/…` and `/forms/en/…`
     * are untouched — and asset() does not pass through format() at all.
     *
     * Guarded: this runs on every generated URL including during migrations and
     * on a fresh install, where the languages table may not exist yet.
     */
    protected function hideDefaultLocaleFromUrls(): void
    {
        try {
            $default = Language::defaultCode();
        } catch (Throwable) {
            return;
        }

        URL::formatPathUsing(function (string $path) use ($default) {
            $prefix = '/'.$default;

            if ($path === $prefix) {
                return '/';
            }

            return str_starts_with($path, $prefix.'/')
                ? substr($path, strlen($prefix))
                : $path;
        });
    }
}
