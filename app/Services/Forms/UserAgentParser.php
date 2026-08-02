<?php

namespace App\Services\Forms;

/**
 * Browser, OS and device from a user-agent string.
 *
 * DELIBERATELY SMALL. A real UA database is a dependency, a data file and a
 * quarterly update, and this module needs three columns to draw three charts —
 * not a device catalogue. It gets the shipping browsers and platforms right and
 * answers `null` for everything else rather than guessing.
 *
 * THE RAW STRING IS ALWAYS STORED ALONGSIDE (`form_submissions.user_agent`), so
 * the day this is not good enough, a better parser can backfill every row that
 * was ever written. That is the whole reason the raw column exists — never drop
 * it in favour of the parsed ones.
 *
 * ORDER MATTERS in both maps. Every Chromium browser says "Chrome", Edge says
 * both "Chrome" and "Edg", and Safari appears in the UA of things that are not
 * Safari — so the specific tokens are tested before the generic ones and the
 * first hit wins.
 */
class UserAgentParser
{
    /** Token => label, most specific first. */
    protected const BROWSERS = [
        'YaBrowser' => 'Yandex',
        'SamsungBrowser' => 'Samsung Internet',
        'UCBrowser' => 'UC Browser',
        'OPiOS' => 'Opera',
        'OPR/' => 'Opera',
        'Opera' => 'Opera',
        'Edg' => 'Edge',
        'Vivaldi' => 'Vivaldi',
        'Brave' => 'Brave',
        'DuckDuckGo' => 'DuckDuckGo',
        'FxiOS' => 'Firefox',
        'Firefox' => 'Firefox',
        'CriOS' => 'Chrome',
        'Chromium' => 'Chromium',
        'Chrome' => 'Chrome',
        'Safari' => 'Safari',
        'MSIE' => 'Internet Explorer',
        'Trident' => 'Internet Explorer',
    ];

    /** Token => label, most specific first. */
    protected const SYSTEMS = [
        'Windows NT 10' => 'Windows 10/11',
        'Windows NT 6.3' => 'Windows 8.1',
        'Windows NT 6.1' => 'Windows 7',
        'Windows' => 'Windows',
        'iPhone' => 'iOS',
        'iPad' => 'iPadOS',
        'iPod' => 'iOS',
        'Android' => 'Android',
        'CrOS' => 'ChromeOS',
        'Mac OS X' => 'macOS',
        'Macintosh' => 'macOS',
        'Ubuntu' => 'Linux',
        'Linux' => 'Linux',
    ];

    /**
     * Anything that says it is a robot. Not a spam defence — the honeypot and
     * the min-time check are — just a device_type that keeps crawler traffic
     * out of the mobile/desktop split.
     */
    protected const BOTS = ['bot', 'crawler', 'spider', 'slurp', 'headlesschrome', 'lighthouse', 'preview', 'monitor'];

    /**
     * @return array{browser: string|null, os: string|null, device_type: string|null}
     */
    public static function parse(?string $agent): array
    {
        if (! is_string($agent) || trim($agent) === '') {
            return ['browser' => null, 'os' => null, 'device_type' => null];
        }

        return [
            'browser' => static::match(static::BROWSERS, $agent),
            'os' => static::match(static::SYSTEMS, $agent),
            'device_type' => static::deviceType($agent),
        ];
    }

    /** First token present in the string, case-insensitively. */
    protected static function match(array $map, string $agent): ?string
    {
        foreach ($map as $token => $label) {
            if (stripos($agent, $token) !== false) {
                return $label;
            }
        }

        return null;
    }

    /**
     * bot | tablet | mobile | desktop.
     *
     * "Mobile" is checked before "Android" because an Android TABLET's UA says
     * Android and omits Mobile — that omission is the only tablet signal Android
     * gives, so reading it the other way round would file every tablet as a
     * phone.
     */
    protected static function deviceType(string $agent): string
    {
        $lower = strtolower($agent);

        foreach (static::BOTS as $token) {
            if (str_contains($lower, $token)) {
                return 'bot';
            }
        }

        if (str_contains($lower, 'ipad') || (str_contains($lower, 'android') && ! str_contains($lower, 'mobile'))) {
            return 'tablet';
        }

        if (str_contains($lower, 'tablet') || str_contains($lower, 'kindle') || str_contains($lower, 'playbook')) {
            return 'tablet';
        }

        if (str_contains($lower, 'mobi') || str_contains($lower, 'iphone') || str_contains($lower, 'ipod')
            || str_contains($lower, 'windows phone') || str_contains($lower, 'opera mini')) {
            return 'mobile';
        }

        return 'desktop';
    }
}
