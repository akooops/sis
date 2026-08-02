<?php

return [

    /*
     * The element catalogue. This array is the source of truth — there is no
     * table mirroring it, because a mirror drifts the moment someone deploys
     * without reseeding and the builder would then offer settings the
     * submit-time compiler cannot read.
     *
     * A new element is one class implementing App\Contracts\Forms\FieldType plus
     * one line here. App\Services\Forms\FieldTypeRegistry builds a code => class
     * map from it, and the builder's inspector renders each element's declared
     * schema through the same SchemaField component the integrations use.
     */
    'field_types' => [
        App\Services\Forms\FieldTypes\HeadingType::class,
        App\Services\Forms\FieldTypes\ParagraphType::class,
        App\Services\Forms\FieldTypes\HtmlType::class,

        App\Services\Forms\FieldTypes\TextType::class,
        App\Services\Forms\FieldTypes\TextareaType::class,
        App\Services\Forms\FieldTypes\NumberType::class,
        App\Services\Forms\FieldTypes\EmailType::class,
        App\Services\Forms\FieldTypes\PhoneType::class,
        App\Services\Forms\FieldTypes\DateType::class,
        App\Services\Forms\FieldTypes\FileType::class,
        App\Services\Forms\FieldTypes\HiddenType::class,

        App\Services\Forms\FieldTypes\SelectType::class,
        App\Services\Forms\FieldTypes\RadioType::class,
        App\Services\Forms\FieldTypes\CheckboxType::class,
        App\Services\Forms\FieldTypes\ConsentType::class,

        App\Services\Forms\FieldTypes\ButtonType::class,
    ],

    /*
     * Forms that ship with the app. FormsSeeder creates these as is_system, so
     * their settings stay editable but their structure does not.
     *
     * Empty on purpose, the same way PagesSeeder ships empty: the capability
     * ships, the content does not.
     */
    'system' => [],

    /*
     * How long a rendered form's submission token stays valid, in minutes. The
     * token carries the honeypot field name and the render timestamp, so this is
     * also how long a visitor has to fill the form in before being asked to
     * reload.
     */
    'token_ttl' => (int) env('FORMS_TOKEN_TTL', 120),

    'spam' => [
        // Default floor for "no human filled this in that fast". A form may
        // override it; null on the form means this value.
        'min_seconds' => (int) env('FORMS_MIN_SUBMIT_SECONDS', 5),

        // Score at or above which a submission is filed as spam.
        'threshold' => (int) env('FORMS_SPAM_THRESHOLD', 50),
    ],

    'uploads' => [
        // Files one anonymous session may upload, so an abandoned form cannot
        // be used as free storage.
        'max_per_session' => (int) env('FORMS_MAX_UPLOADS_PER_SESSION', 10),

        // How long the browser waits for a virus scan before submitting anyway.
        'scan_wait_seconds' => (int) env('FORMS_SCAN_WAIT_SECONDS', 15),
    ],

    'submissions' => [
        // Unfinished submissions older than this are pruned by model:prune.
        // COMPLETED submissions are never pruned — they are the record.
        'prune_after_days' => (int) env('FORMS_PRUNE_AFTER_DAYS', 90),

        // A draft nobody touched for this long is DELETED by model:prune.
        'prune_started_after_hours' => (int) env('FORMS_PRUNE_STARTED_AFTER_HOURS', 24),

        // A draft nobody touched for this long is swept to `abandoned` by
        // forms:close-abandoned. MUST stay well below prune_started_after_hours
        // above, or the nightly prune deletes drafts before they are ever
        // classified and the abandonment funnel reads empty forever.
        'abandon_after_minutes' => (int) env('FORMS_ABANDON_AFTER_MINUTES', 30),

        // Drafts one address may open per hour PER FORM, so a script cannot fill
        // the table. Counted per form on purpose: one ip_hash is often a whole
        // office, campus or carrier NAT, and site-wide the busiest form would
        // spend the allowance for every other form on the site.
        'max_drafts_per_hour' => (int) env('FORMS_MAX_DRAFTS_PER_HOUR', 20),

        // How long a signed link to an answer file stays valid, in minutes.
        // Answer uploads live on the private disk and have no URL of their own,
        // so this is the whole lifetime of a shared link — short on purpose.
        'file_link_ttl' => (int) env('FORMS_FILE_LINK_TTL', 30),

        // Rows read per keyset chunk while the CSV export streams.
        'export_chunk' => (int) env('FORMS_EXPORT_CHUNK', 500),
    ],

    'webhooks' => [
        // Mandatory while the queue is sync: without it a dead endpoint holds
        // the visitor's browser open for the full connection timeout.
        'timeout' => (int) env('FORMS_WEBHOOK_TIMEOUT', 10),
    ],

    'limits' => [
        'submits_per_minute' => (int) env('FORMS_SUBMITS_PER_MINUTE', 10),
        'uploads_per_minute' => (int) env('FORMS_UPLOADS_PER_MINUTE', 20),

        // Beacons. Higher than the others because one visit sends several: the
        // first interaction, every page change, a heartbeat while typing, and
        // one on the way out.
        'telemetry_per_minute' => (int) env('FORMS_TELEMETRY_PER_MINUTE', 60),
    ],

    'geo' => [
        /*
         * Headers a CDN or load balancer uses to report the visitor's country.
         * Trusted ONLY when the request arrived through a proxy TrustProxies
         * recognises — otherwise any client could set one and walk straight past
         * a country block.
         */
        'country_headers' => ['CF-IPCountry', 'CloudFront-Viewer-Country', 'X-Vercel-IP-Country', 'X-Country-Code'],
    ],

    /*
     * What the public page measures. Everything here is COUNTS and TIMINGS —
     * keystroke content and pasted text are never transmitted or stored.
     */
    'capture' => [
        'timing' => (bool) env('FORMS_CAPTURE_TIMING', true),
        'behaviour' => (bool) env('FORMS_CAPTURE_BEHAVIOUR', true),
        'scroll' => (bool) env('FORMS_CAPTURE_SCROLL', true),
        'keystrokes' => (bool) env('FORMS_CAPTURE_KEYSTROKES', true),

        // Off by default: it needs a high-frequency listener to measure
        // something no chart reads, and it is the first thing a privacy review
        // asks about.
        'mouse' => (bool) env('FORMS_CAPTURE_MOUSE', false),
    ],
];
