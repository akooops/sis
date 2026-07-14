<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Applies the user's chosen locale (stored in the session) for the request, so
 * the shared i18n prop + all translations render in that language. Runs after
 * StartSession and before HandleInertiaRequests.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $supported = (array) config('app.supported_locales', ['en']);
        // API-key (stateless) requests have no session — skip silently there.
        $locale = $request->hasSession() ? $request->session()->get('locale') : null;

        if (is_string($locale) && in_array($locale, $supported, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
