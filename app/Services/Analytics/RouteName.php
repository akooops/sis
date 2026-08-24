<?php

namespace App\Services\Analytics;

/**
 * One page, one name — whichever of the two site route groups matched.
 *
 * THIS IS THE WHOLE REASON THE REPORTS ARE NOT SPLIT BY LOCALE. routes/web.php
 * registers the public site TWICE: once under `web.site.` with a {locale}
 * prefix and once under `web.site.root.` without one. So /contact, /en/contact
 * and /ar/contact are three URLs and two route names for a single page. Group
 * on the raw name or the raw path and every report shows each page up to ten
 * times, once per locale, none of them the real total.
 *
 * BOTH THE WRITER AND THE READER CALL THIS. The middleware normalises before it
 * stores, and TrafficAnalytics normalises SiteContext::LINK_ROUTES before it
 * joins against what was stored. Two copies of this regex would silently stop
 * matching the day one of them changed, and the symptom would be an empty
 * top-content table rather than an error.
 */
final class RouteName
{
    /** 'web.site.root.articles.show' | 'web.site.articles.show' -> 'articles.show' */
    public static function normalise(?string $name): ?string
    {
        if ($name === null || $name === '') {
            return null;
        }

        return preg_replace('/^web\.site\.(root\.)?/', '', $name);
    }

    /** Whether a route belongs to the public site at all. */
    public static function isSite(?string $name): bool
    {
        return is_string($name) && str_starts_with($name, 'web.site.');
    }
}
