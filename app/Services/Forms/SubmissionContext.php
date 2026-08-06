<?php

namespace App\Services\Forms;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Everything a submission row knows about WHERE it came from, derived from the
 * request rather than measured in the browser.
 *
 * One class because both writers need the identical answer: the telemetry
 * beacon writes these onto the draft, and the submit pipeline writes them again
 * onto the row it completes. A visitor who never triggers a beacon (JavaScript
 * off, or a very fast submit) must still get a fully populated row, so the
 * submit path cannot rely on the draft having been there first.
 *
 * NOTHING HERE IS TRUSTED FOR A SECURITY DECISION. The country comes from
 * GeoResolver, which refuses to read its headers unless the request arrived
 * through a recognised proxy; the client block is whatever the page posted. They
 * describe a visit, they do not gate one — the blocks and caps run off ip_hash
 * and the guard.
 */
class SubmissionContext
{
    public function __construct(protected GeoResolver $geo) {}

    /**
     * The full column set, ready to merge into a FormSubmission.
     *
     * @param  array<string, mixed>  $client  the browser's own report (see resources/site/js/lib/forms/telemetry.js)
     * @return array<string, mixed>
     */
    public function all(Request $request, array $client = []): array
    {
        return array_merge(
            $this->technical($request, $client),
            $this->geo($request),
            $this->acquisition($request, $client),
        );
    }

    /**
     * Device and browser.
     *
     * The parsed columns are a convenience for charts; `user_agent` keeps the
     * raw string so a better parser can backfill them later.
     *
     * @param  array<string, mixed>  $client
     * @return array<string, mixed>
     */
    public function technical(Request $request, array $client = []): array
    {
        $agent = (string) $request->userAgent();

        return array_merge(UserAgentParser::parse($agent), [
            'user_agent' => Str::limit($agent, 500, ''),
            'screen_w' => $this->pixels($client['screen_w'] ?? null),
            'screen_h' => $this->pixels($client['screen_h'] ?? null),
            'viewport_w' => $this->pixels($client['viewport_w'] ?? null),
            'viewport_h' => $this->pixels($client['viewport_h'] ?? null),
            // The header is the fallback, not the primary: it is the whole
            // Accept-Language list, while the client reports the one locale the
            // browser is actually running in.
            'browser_language' => $this->string($client['browser_language'] ?? $request->header('Accept-Language'), 32),
            'connection' => $this->string($client['connection'] ?? null, 32),
            'timezone' => $this->string($client['timezone'] ?? null, 64),
            'timezone_offset_minutes' => $this->offset($client['timezone_offset_minutes'] ?? null),
        ]);
    }

    /**
     * Where the visitor is.
     *
     * `country_id` is resolved as well as the code so the admin can join to the
     * countries table, and it stays null when the code names a country nobody
     * seeded — the code is still recorded either way.
     *
     * @return array<string, mixed>
     */
    public function geo(Request $request): array
    {
        $code = $this->geo->countryCode($request);

        return [
            'country_code' => $code,
            'country_id' => $code ? Country::where('code', $code)->value('id') : null,
        ];
    }

    /**
     * How they got here.
     *
     * `landing_url` is the browser's — the first URL of the visit, which the
     * submit POST no longer knows because it is itself a different request.
     * `page_url` is deliberately the server's own view.
     *
     * @param  array<string, mixed>  $client
     * @return array<string, mixed>
     */
    public function acquisition(Request $request, array $client = []): array
    {
        /*
         * The browser's referrer wins, and `??` is deliberately NOT enough:
         * document.referrer is the EMPTY STRING on a direct visit, not null, so
         * `$client['referrer'] ?? $header` kept the '' and every direct arrival
         * was classified as `internal` — the single largest acquisition bucket,
         * silently mislabelled. Coalesce on emptiness, not on existence.
         */
        $referrer = $this->string($client['referrer'] ?? null, 2000)
            ?: $this->string($request->headers->get('referer'), 2000);

        $utm = $this->utm($request, $client);

        /*
         * The page the VISITOR was on, not the URL this request happens to have.
         *
         * A telemetry beacon POSTs to /forms/{locale}/{slug}/telemetry, so
         * $request->fullUrl() there is the endpoint — every draft and abandoned
         * row recorded the beacon URL as the page the visitor was looking at,
         * which is both useless and wrong. The client reports its own location;
         * fall back to the request only when it did not.
         */
        $pageUrl = $this->string($client['page_url'] ?? null, 2000)
            ?: Str::limit($request->fullUrl(), 2000, '');

        return [
            'page_url' => $pageUrl,
            'landing_url' => $this->string($client['landing_url'] ?? null, 2000) ?: $pageUrl,
            'referrer' => $referrer,
            'utm' => $utm,
            'entry_point' => $this->entryPoint($referrer, $utm),
        ];
    }

    /**
     * The campaign parameters, from the query string first and the browser's
     * landing URL second.
     *
     * The fallback is what makes a multi-page form work at all: the visitor
     * arrives with ?utm_source=… on the GET, and the POST that follows carries
     * none of it.
     *
     * @param  array<string, mixed>  $client
     * @return array<string, string>|null
     */
    protected function utm(Request $request, array $client = []): ?array
    {
        $landing = [];

        if (is_string($client['landing_url'] ?? null) && str_contains($client['landing_url'], '?')) {
            parse_str((string) parse_url($client['landing_url'], PHP_URL_QUERY), $landing);
        }

        $utm = [];

        foreach (['source', 'medium', 'campaign', 'term', 'content'] as $key) {
            $value = $request->query("utm_{$key}") ?? ($landing["utm_{$key}"] ?? null);

            if (is_string($value) && $value !== '') {
                $utm[$key] = Str::limit($value, 190, '');
            }
        }

        return $utm ?: null;
    }

    /**
     * A one-word bucket for the acquisition chart.
     *
     * campaign > internal > search > social > referral > direct. Campaign wins
     * outright: a tagged link is a deliberate statement about where the traffic
     * came from and it beats whatever the referrer header happens to say.
     *
     * @param  array<string, string>|null  $utm
     */
    protected function entryPoint(?string $referrer, ?array $utm): string
    {
        if ($utm) {
            return 'campaign';
        }

        $host = $referrer ? strtolower((string) parse_url($referrer, PHP_URL_HOST)) : '';

        if ($host === '') {
            return 'direct';
        }

        if ($host === strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST))) {
            return 'internal';
        }

        foreach (['google.', 'bing.', 'yahoo.', 'duckduckgo.', 'yandex.', 'baidu.', 'ecosia.', 'qwant.'] as $needle) {
            if (str_contains($host, $needle)) {
                return 'search';
            }
        }

        foreach (['facebook.', 'instagram.', 'twitter.', 'x.com', 't.co', 'linkedin.', 'lnkd.in', 'youtube.',
            'tiktok.', 'reddit.', 'pinterest.', 'whatsapp.', 'telegram.', 't.me'] as $needle) {
            if (str_contains($host, $needle)) {
                return 'social';
            }
        }

        return 'referral';
    }

    /** A screen or viewport dimension, clamped to the unsigned smallint columns. */
    protected function pixels(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $pixels = (int) $value;

        return $pixels > 0 ? min($pixels, 65535) : null;
    }

    /** Minutes ahead of UTC, inside the signed smallint the column is. */
    protected function offset(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $minutes = (int) $value;

        return $minutes >= -1440 && $minutes <= 1440 ? $minutes : null;
    }

    protected function string(mixed $value, int $max): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Str::limit($value, $max, '');
    }
}
