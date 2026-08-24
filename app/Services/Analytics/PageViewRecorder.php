<?php

namespace App\Services\Analytics;

use App\Models\SitePageView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns one satisfied request into one row.
 *
 * INSERT ONLY. There is no visit row to find, extend or close, which is what
 * keeps this class short and what keeps it correct under concurrency: two
 * simultaneous page views are two inserts and cannot interfere.
 *
 * EVERYTHING IS READ OFF THE REQUEST AND THE RESPONSE. Nothing is carried over
 * from handle(), because the container hands terminate() a different middleware
 * instance - see RecordPageView.
 */
class PageViewRecorder
{
    public function __construct(protected GeoResolver $geo) {}

    public function record(Request $request, Response $response): void
    {
        if (! $this->shouldRecord($request, $response)) {
            return;
        }

        $page = $this->page($request);

        if ($page === null || in_array($page['route'], (array) config('analytics.ignore_routes', []), true)) {
            return;
        }

        $device = UserAgentParser::parse((string) $request->userAgent());
        $referrer = $this->referrer($request);
        $utm = Acquisition::utm($request->query());

        DB::table('site_page_views')->insert([
            // From the model's generator, never Str::ulid(): HasUlids lowercases
            // what the helper returns, and mixing the two is what left
            // visit_slots holding the only uppercase ids in the database.
            'id' => (new SitePageView)->newUniqueId(),
            'viewed_at' => now(),

            'route_name' => $page['route'],
            'slug' => $page['slug'],
            'path' => $page['path'],
            'locale' => $page['locale'],

            'visit_key' => $this->visitKey($request),
            'visitor_key' => $this->visitorKey($request),

            'entry_point' => Acquisition::entryPoint($referrer, $utm),
            'referrer_host' => Acquisition::host($referrer),
            'utm_campaign' => $utm['campaign'] ?? null,

            'country_code' => $this->geo->countryCode($request),
            'device_type' => $device['device_type'],
            'browser' => $device['browser'],
            'os' => $device['os'],
            'is_bot' => $device['device_type'] === 'bot',
        ]);
    }

    /**
     * Whether this request was a person reading a page.
     *
     * Ordered cheapest first. Every rule exists because something that is not a
     * page view would otherwise be counted as one.
     */
    public function shouldRecord(Request $request, Response $response): bool
    {
        if (! config('analytics.enabled', true)) {
            return false;
        }

        // HEAD is a probe, not a read. isMethod('GET') is already false for it,
        // because Symfony keeps the real verb even while routing it as GET.
        if (! $request->isMethod('GET')) {
            return false;
        }

        /*
         * THE LOAD-BEARING RULE.
         *
         * `pages.show` is a {slug} catch-all, so /wp-login.php, /.env and every
         * mistyped link MATCH a real site route and reach this middleware. The
         * 404 is rendered by site::pages.error as text/html, so the content-type
         * rule below would NOT catch them - without this one the top-content
         * table fills with pages that have never existed. It also drops every
         * redirect, and the /hr-read-as-a-locale 404 from SetLocale.
         */
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        // Drops visits.slots and facilities.slots - the JSON calendar feeds that
        // live inside the site group - without having to name them.
        if (! Str::startsWith((string) $response->headers->get('Content-Type'), 'text/html')) {
            return false;
        }

        if ($this->isPrefetch($request)) {
            return false;
        }

        // A real navigation says `document`. `empty`, `image` or `script` means
        // a subresource somehow matched a page route.
        $dest = $request->headers->get('Sec-Fetch-Dest');

        if (is_string($dest) && $dest !== '' && ! in_array($dest, ['document', 'iframe'], true)) {
            return false;
        }

        if ($request->ajax() || $request->expectsJson()) {
            return false;
        }

        // No session means no visit key. Also what keeps this inert under
        // SESSION_DRIVER=array, where every page view would be a fresh visit.
        return $request->hasSession() && RouteName::isSite($request->route()?->getName());
    }

    /**
     * Browsers speculatively load links, and four generations of header say so.
     *
     * Chrome prefetches on hover, so without this the busiest "pages" would be
     * whatever the main navigation happens to link to.
     */
    protected function isPrefetch(Request $request): bool
    {
        // Current spec, and the value can be a list: "prefetch;prerender".
        $purpose = (string) $request->headers->get('Sec-Purpose');

        if (str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender')) {
            return true;
        }

        return $request->headers->get('Purpose') === 'prefetch'
            || $request->headers->get('X-Purpose') === 'preview'
            || $request->headers->get('X-Moz') === 'prefetch';
    }

    /**
     * The page's identity: normalised route name plus slug.
     *
     * NEVER THE PATH. The site is registered twice, so grouping on the raw route
     * name or the raw URL shows every page once per locale and none of them the
     * real total. `path` rides along for a human reading one row, and nothing
     * ever groups on it.
     *
     * @return array{route: string, slug: string, path: string, locale: string}|null
     */
    protected function page(Request $request): ?array
    {
        $route = $request->route();
        $name = RouteName::normalise($route?->getName());

        if ($name === null) {
            return null;
        }

        $locale = $route->parameter('locale');
        $path = '/'.ltrim($request->path(), '/');

        // Strip the locale segment so /ar/contact and /contact agree here too.
        if (is_string($locale) && $locale !== '') {
            $path = Str::start((string) Str::after($path, '/'.$locale), '/');
        }

        return [
            'route' => $name,
            'slug' => Str::limit((string) ($route->parameter('slug') ?? ''), 191, ''),
            'path' => Str::limit($path === '' ? '/' : $path, 255, ''),
            // App::getLocale(), not the route parameter: SetLocale has already
            // filled it for BOTH registrations, and the unprefixed half has no
            // such parameter to read.
            'locale' => App::getLocale(),
        ];
    }

    /**
     * The visit this view belongs to.
     *
     * HMAC, NEVER THE RAW SESSION ID - that id is the cookie value, i.e. a
     * credential, which is why Session::$hidden hides it and why routes bind on
     * the ULID instead. Same construction as SubmissionGuard::hashIp(), so this
     * app has one recipe rather than two.
     *
     * A visitor with cookies disabled gets a new id per request and so reads as
     * a series of one-page visits. Bounded, mostly bots, and the honest failure:
     * the alternative is fingerprinting.
     */
    protected function visitKey(Request $request): string
    {
        return hash_hmac('sha256', (string) $request->session()->getId(), (string) config('app.key'));
    }

    /**
     * The person, approximately.
     *
     * ip + user-agent, with NO rotating salt, so that count(distinct ...) is a
     * real answer over a month rather than "visitor-days". It under-counts a
     * computer lab behind one NAT, which reads as a single visitor, and
     * over-counts anyone moving from Wi-Fi to cellular. The dashboard tile says
     * "approximate" for exactly that reason.
     */
    protected function visitorKey(Request $request): string
    {
        return hash_hmac('sha256', $request->ip().'|'.$request->userAgent(), (string) config('app.key'));
    }

    /** The referring URL, or null when there was not one. */
    protected function referrer(Request $request): ?string
    {
        $referrer = $request->headers->get('referer');

        return is_string($referrer) && $referrer !== '' ? Str::limit($referrer, 500, '') : null;
    }
}
