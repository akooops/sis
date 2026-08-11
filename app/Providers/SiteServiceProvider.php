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
        View::composer('site::*', function (ViewInstance $view) {
            $view->with('site', $this->app->make(SiteContext::class));
        });
    }
}
