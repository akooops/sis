<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the `{locale}` URL segment into the request's locale.
 *
 * The locale is an explicit URL segment rather than a negotiated header or a
 * session value, because a public link has to render the same page for everyone
 * who opens it — the old site put the locale in the session, so one URL served
 * two languages and neither could be canonical or indexed separately.
 *
 * Route::pattern in RouteServiceProvider already rejects anything that is not
 * two lowercase letters; this narrows that to the codes actually ENABLED in the
 * languages table. A disabled or unknown locale is a 404 and not a redirect: the
 * URL names a page that does not exist, and quietly serving a different language
 * would let a typo rank in search results.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale') ?? Language::defaultCode();

        abort_unless(in_array($locale, Language::enabledCodes(), true), 404);
    
        App::setLocale($locale);

        // __() alone is not enough: the site prints article dates and event
        // ranges, and Carbon has its own locale for month and weekday names.
        Carbon::setLocale($locale);

        /*
         * ALWAYS, including for the default locale.
         *
         * Every site route carries a {locale} placeholder, so route() cannot
         * BUILD one without a value — leaving the default unset makes every
         * route('web.site.…') call on an English page throw "Missing required
         * parameter" and 500 the page.
         *
         * Keeping `/en` out of the generated URL is a separate job, done after
         * the URL exists: SiteServiceProvider::hideDefaultLocaleFromUrls()
         * strips the default's segment in UrlGenerator::format().
         */
        URL::defaults(['locale' => $locale]);

        return $next($request);
    }
}
