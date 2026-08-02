<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Public form endpoints. Keyed by IP because the caller is anonymous —
        // which is also why App\Http\Middleware\TrustProxies must be configured
        // in production, or every visitor shares one bucket.
        RateLimiter::for('form-submits', function (Request $request) {
            return Limit::perMinute((int) config('forms.limits.submits_per_minute', 10))->by($request->ip());
        });

        RateLimiter::for('form-uploads', function (Request $request) {
            return Limit::perMinute((int) config('forms.limits.uploads_per_minute', 20))->by($request->ip());
        });

        // The analytics beacon. CSRF-excepted (sendBeacon cannot set a header),
        // so this and the submission token are what stand between the endpoint
        // and anyone at all.
        RateLimiter::for('form-telemetry', function (Request $request) {
            return Limit::perMinute((int) config('forms.limits.telemetry_per_minute', 60))->by($request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
