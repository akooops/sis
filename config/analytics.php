<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Recording
    |--------------------------------------------------------------------------
    |
    | The public site writes ONE ROW PER PAGE VIEW and nothing else. There is no
    | beacon, no client-side script and no per-field telemetry here — that lives
    | in the forms module, which measures somebody filling in one form. This
    | measures traffic, and Google Analytics carries the deeper reporting.
    |
    */

    'enabled' => (bool) env('ANALYTICS_ENABLED', true),

    /*
     * How long a page view is kept.
     *
     * SitePageView is MassPrunable and `model:prune` already runs daily, so
     * this is the only knob — there is no bespoke command. A year is what makes
     * "this month last year" answerable; older detail lives in GA.
     */
    'retention_days' => (int) env('ANALYTICS_RETENTION_DAYS', 365),

    /*
     * Routes inside the public site group that are not pages.
     *
     * The text/html check already excludes these two JSON calendar feeds; this
     * is the explicit belt, so the day somebody adds another JSON route to the
     * site group it is a config line rather than a mystery in the reports.
     */
    'ignore_routes' => [
        'visits.slots',
        'facilities.slots',
    ],

    /*
    |--------------------------------------------------------------------------
    | Geography
    |--------------------------------------------------------------------------
    |
    | Two sources, trusted differently — see App\Services\Analytics\GeoResolver.
    | Shared with the forms module's country blocking, which reads the same
    | resolver: installing the database below makes a country block on a site
    | with no CDN start working, where today it fails open for everyone.
    |
    */

    'geo' => [

        /*
         * Headers a CDN or load balancer uses to report the visitor's country.
         * Trusted ONLY when the request arrived through a proxy TrustProxies
         * recognises — otherwise any client could set one and walk straight past
         * a country block.
         *
         * Moved here from config/forms.php: the resolver moved, and two lists
         * that can disagree about which headers to trust is a security-relevant
         * disagreement, not a duplication nit.
         */
        'country_headers' => ['CF-IPCountry', 'CloudFront-Viewer-Country', 'X-Vercel-IP-Country', 'X-Country-Code'],

        /*
         * Which vendor supplies the .mmdb. Both ship the same binary format and
         * are read by the same maxmind-db/reader, so this is an operational
         * choice and not a code one.
         *
         *   dbip    - DB-IP Lite Country. A plain URL, no account, no licence
         *             key, so `analytics:geoip-update` works on a fresh clone.
         *             CC-BY-4.0: the Countries card carries the attribution.
         *   maxmind - GeoLite2 Country. No attribution line, but a free MaxMind
         *             account and a licence key in .env before it downloads.
         */
        'source' => env('ANALYTICS_GEO_SOURCE', 'dbip'),

        /*
         * Where the database lives. NOT bundled — 40 MB does not belong in git
         * and the licence would have to travel with it. Everything fails open to
         * null until `analytics:geoip-update` has run, so this is never a
         * deployment blocker.
         */
        'database' => storage_path('app/geoip/country.mmdb'),

        'maxmind' => [
            'edition' => env('MAXMIND_EDITION', 'GeoLite2-Country'),
            'license_key' => env('MAXMIND_LICENSE_KEY'),
        ],
    ],

];
