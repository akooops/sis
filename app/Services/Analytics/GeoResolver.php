<?php

namespace App\Services\Analytics;

use App\Models\Country;
use Illuminate\Http\Request;
use MaxMind\Db\Reader;
use Throwable;

/**
 * Where a visitor is: a CDN's country header first, a local IP database second.
 *
 * TWO SOURCES, AND THEY ARE TRUSTED DIFFERENTLY. The header is an ordinary
 * request header — any client can set CF-IPCountry to whatever it likes — so it
 * is honoured ONLY behind a proxy TrustProxies recognises, or anybody could walk
 * straight past a country block by claiming to be somewhere else. The database
 * needs no such gate: it reads $request->ip(), which is the real remote address
 * unless TrustProxies is configured and the real client address when it is.
 * Nothing a client sends can move it.
 *
 * FAILS OPEN, ALWAYS. Missing file, truncated file, wrong format, private
 * address, unparsable address — every path returns null and nothing throws.
 * This runs while rendering a public form and while recording a page view: a
 * misconfigured header or a half-downloaded database must never take the site
 * offline, which is a far worse failure than an unknown country.
 *
 * NOTE FOR ANYONE DEBUGGING AN EMPTY COUNTRIES CHART IN DEV: private and
 * reserved ranges are rejected before the file is even opened, and in
 * development every request comes from 127.0.0.1. An empty result there is
 * CORRECT. Do not "fix" it by dropping the range guard — that would start
 * recording a null country for every RFC1918 address behind a production load
 * balancer, and the reader would be asked for addresses no database contains.
 */
class GeoResolver
{
    /**
     * Memoised per PROCESS, not per instance.
     *
     * GeoResolver is injected into four separate constructors (SubmissionGuard,
     * SubmissionContext, SubmitController, FormPresenter) and the container
     * builds a fresh one for each, so an instance property would open the same
     * database up to four times in one request. `$opened` is separate from
     * `$reader` because null is a real, cacheable answer — without it, an
     * install with no database would re-stat the missing file on every lookup.
     */
    private static ?Reader $reader = null;

    private static bool $opened = false;

    /** ISO alpha-2, or null when it cannot be determined. */
    public function countryCode(Request $request): ?string
    {
        return $this->fromHeader($request) ?? $this->fromDatabase($request->ip());
    }

    public function country(Request $request): ?Country
    {
        $code = $this->countryCode($request);

        return $code ? Country::where('code', $code)->first() : null;
    }

    /**
     * Whether geo is actually usable, for the admin warning banner.
     *
     * EITHER source counts. Reporting "not configured" on a server that has the
     * database installed would tell an admin their country blocks are inert
     * while they are in fact blocking people.
     */
    public function isConfigured(Request $request): bool
    {
        return $request->isFromTrustedProxy() || static::reader() !== null;
    }

    /** The CDN's answer. Trusted only behind a recognised proxy. */
    protected function fromHeader(Request $request): ?string
    {
        if (! $request->isFromTrustedProxy()) {
            return null;
        }

        foreach (config('analytics.geo.country_headers', []) as $header) {
            $value = $request->header($header);

            if (is_string($value) && preg_match('/^[A-Za-z]{2}$/', $value)) {
                // Cloudflare sends XX for "unknown" and T1 for Tor.
                $code = strtoupper($value);

                return in_array($code, ['XX', 'T1'], true) ? null : $code;
            }
        }

        return null;
    }

    /** The local database's answer, by address. */
    protected function fromDatabase(?string $ip): ?string
    {
        // Skipped before the file is opened: a loopback, LAN or reserved address
        // is in no database, and asking costs a lookup to be told so.
        if (! is_string($ip) || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        if (! $reader = static::reader()) {
            return null;
        }

        try {
            $record = $reader->get($ip);
        } catch (Throwable) {
            // A corrupt database or an address family the file does not cover.
            return null;
        }

        $code = $record['country']['iso_code'] ?? $record['registered_country']['iso_code'] ?? null;

        return is_string($code) && preg_match('/^[A-Z]{2}$/', $code) ? $code : null;
    }

    /** The open database, or null when there is not one to open. */
    protected static function reader(): ?Reader
    {
        if (static::$opened) {
            return static::$reader;
        }

        static::$opened = true;

        $path = (string) config('analytics.geo.database');

        // class_exists so the app still boots before `composer install` has run
        // the package in — the whole class is optional by design.
        if ($path === '' || ! is_file($path) || ! class_exists(Reader::class)) {
            return static::$reader = null;
        }

        try {
            return static::$reader = new Reader($path);
        } catch (Throwable) {
            return static::$reader = null;
        }
    }

    /** Drop the memoised handle. For the update command, after a swap. */
    public static function flush(): void
    {
        static::$reader = null;
        static::$opened = false;
    }
}
