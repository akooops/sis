<?php

namespace App\Services\Analytics;

use Illuminate\Support\Str;

/**
 * Where a visit came from, as one word and one host.
 *
 * EXTRACTED FROM SubmissionContext SO THERE IS ONE LIST, NOT TWO. The search
 * and social host needles below are the only such lists in the app, and a form
 * submission and a page view must bucket the same referrer the same way or the
 * two dashboards would disagree about what "social" means. SubmissionContext
 * now delegates here; nothing was reworded on the way across.
 */
final class Acquisition
{
    /** Hosts that mean somebody searched for us. */
    private const SEARCH = ['google.', 'bing.', 'yahoo.', 'duckduckgo.', 'yandex.', 'baidu.', 'ecosia.', 'qwant.'];

    /** Hosts that mean somebody shared us. */
    private const SOCIAL = ['facebook.', 'instagram.', 'twitter.', 'x.com', 't.co', 'linkedin.', 'lnkd.in', 'youtube.',
        'tiktok.', 'reddit.', 'pinterest.', 'whatsapp.', 'telegram.', 't.me'];

    /**
     * A one-word bucket for the acquisition chart.
     *
     * campaign > internal > search > social > referral > direct. Campaign wins
     * outright: a tagged link is a deliberate statement about where the traffic
     * came from and it beats whatever the referrer header happens to say.
     *
     * @param  array<string, string>|null  $utm
     */
    public static function entryPoint(?string $referrer, ?array $utm): string
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

        foreach (self::SEARCH as $needle) {
            if (str_contains($host, $needle)) {
                return 'search';
            }
        }

        foreach (self::SOCIAL as $needle) {
            if (str_contains($host, $needle)) {
                return 'social';
            }
        }

        return 'referral';
    }

    /**
     * The referrer's host, lowercased and without a leading `www.`.
     *
     * NORMALISED AT WRITE TIME ON PURPOSE. SubmissionAnalytics has to unpick a
     * host out of a raw URL in SQL on every dashboard load — three nested
     * substring_index calls — because form_submissions only stores the full
     * referrer. Storing the host once here makes the read a plain GROUP BY.
     */
    public static function host(?string $referrer): ?string
    {
        if (! is_string($referrer) || $referrer === '') {
            return null;
        }

        $host = strtolower((string) parse_url($referrer, PHP_URL_HOST));

        if ($host === '') {
            return null;
        }

        // Only a LEADING www. — Str::after would turn "mywww.example.com"
        // into "example.com", quietly merging two different hosts.
        $host = Str::startsWith($host, 'www.') ? substr($host, 4) : $host;

        return Str::limit($host, 190, '') ?: null;
    }

    /**
     * The campaign parameters present on this request's query string.
     *
     * Query string only — unlike the forms version, which also digs them out of
     * a browser-reported landing URL because its POST arrives on a different
     * request. A page view IS the arrival, so there is nothing to reconstruct.
     *
     * @return array<string, string>|null
     */
    public static function utm(array $query): ?array
    {
        $utm = [];

        foreach (['source', 'medium', 'campaign', 'term', 'content'] as $key) {
            $value = $query["utm_{$key}"] ?? null;

            if (is_string($value) && $value !== '') {
                $utm[$key] = Str::limit($value, 190, '');
            }
        }

        return $utm ?: null;
    }
}
