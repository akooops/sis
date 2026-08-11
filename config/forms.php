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
     * These two exist because the public site RESOLVES THEM BY SLUG:
     * /{locale}/contact and /{locale}/inquiries each look up one of these and
     * render it with the ordinary form renderer, so renaming a slug would
     * 404 a page. is_system is what makes that safe — UpdateFormData freezes the
     * slug with Rule::in([$form->slug]), Form::isLocked() blocks structural
     * edits in the builder, and FormsController::destroy refuses the delete.
     * Copy, wording, notification routing and webhooks all stay editable.
     *
     * LABELS ARE CATALOGUE KEYS, NOT STRINGS. `label_key`, `title_key` and
     * `confirmation_key` name entries in the `forms` group of
     * config/translations.php, and FormsSeeder resolves each one across every
     * seeded locale — so both forms ship translated into all nine rather than
     * English-only with eight blanks for an admin to fill.
     *
     * Keys rather than nine inline strings per field because the wording already
     * exists there and is edited on the Translations page; two copies would
     * disagree the first time someone corrected one of them. An admin can still
     * override any label afterwards in the builder — the seed is a starting
     * point, not a binding.
     *
     * Settings and validation keys below are the ones the FieldType classes
     * actually declare — `email` and `phone` declare NONE, so nothing is passed
     * to them. Check app/Services/Forms/FieldTypes/*.php before adding a key;
     * an unknown one is silently ignored rather than rejected.
     */
    'system' => [
        [
            'slug' => 'contact',
            'name' => 'Contact',
            'title_key' => 'forms.contact.title',
            'confirmation_key' => 'forms.contact.success',
            'pages' => [
                [
                    'name' => 'Contact',
                    'fields' => [
                        ['type' => 'text', 'key' => 'name', 'label_key' => 'forms.contact.name', 'is_required' => true, 'validation' => ['max_length' => 120]],
                        ['type' => 'email', 'key' => 'email', 'label_key' => 'forms.contact.email', 'is_required' => true],
                        ['type' => 'phone', 'key' => 'phone', 'label_key' => 'forms.contact.phone'],
                        ['type' => 'text', 'key' => 'subject', 'label_key' => 'forms.contact.subject', 'is_required' => true, 'validation' => ['max_length' => 160]],
                        ['type' => 'textarea', 'key' => 'message', 'label_key' => 'forms.contact.message', 'is_required' => true, 'settings' => ['rows' => 8], 'validation' => ['max_length' => 4000]],
                        ['type' => 'button', 'key' => 'submit', 'label_key' => 'forms.contact.submit', 'settings' => ['action' => 'submit', 'variant' => 'primary']],
                    ],
                ],
            ],
        ],

        [
            'slug' => 'inquiries',
            'name' => 'Admissions inquiry',
            'title_key' => 'forms.inquiry.title',
            'confirmation_key' => 'forms.inquiry.success',
            'pages' => [
                [
                    'name' => 'Inquiry',
                    'fields' => [
                        ['type' => 'text', 'key' => 'guardian_name', 'label_key' => 'forms.inquiry.guardian_name', 'is_required' => true, 'validation' => ['max_length' => 120]],
                        ['type' => 'email', 'key' => 'email', 'label_key' => 'forms.contact.email', 'is_required' => true],
                        ['type' => 'phone', 'key' => 'phone', 'label_key' => 'forms.contact.phone', 'is_required' => true],
                        ['type' => 'text', 'key' => 'student_name', 'label_key' => 'forms.inquiry.student_name', 'is_required' => true, 'validation' => ['max_length' => 120]],
                        // 'today' is resolved to a concrete date by DateType::resolve()
                        // at render time — a relative string cannot be compared
                        // against a date_format rule, which is why it resolves there.
                        ['type' => 'date', 'key' => 'student_birthdate', 'label_key' => 'forms.inquiry.student_birthdate', 'is_required' => true, 'validation' => ['max_date' => 'today']],
                        ['type' => 'text', 'key' => 'student_school', 'label_key' => 'forms.inquiry.student_school', 'validation' => ['max_length' => 160]],
                        [
                            'type' => 'select',
                            'key' => 'academic_year',
                            'label_key' => 'forms.inquiry.academic_year',
                            'is_required' => true,
                            // A fixed list rather than a generated one: a seeder
                            // runs once, and a range computed from the seed date
                            // would silently go stale. Extend it here.
                            'options' => [
                                ['value' => '2026/2027', 'label' => '2026/2027'],
                                ['value' => '2027/2028', 'label' => '2027/2028'],
                                ['value' => '2028/2029', 'label' => '2028/2029'],
                                ['value' => '2029/2030', 'label' => '2029/2030'],
                                ['value' => '2030/2031', 'label' => '2030/2031'],
                            ],
                        ],
                        [
                            'type' => 'select',
                            'key' => 'grade',
                            'label_key' => 'forms.inquiry.grade',
                            'is_required' => true,
                            // Option VALUES are never translated — the same answer
                            // has to read identically whatever language it was
                            // given in, which is what makes an export comparable.
                            'options' => [
                                ['value' => 'prek', 'label' => 'PreK'],
                                ['value' => 'kg1', 'label' => 'KG1'],
                                ['value' => 'kg2', 'label' => 'KG2'],
                                ['value' => 'g1', 'label' => 'Grade 1'],
                                ['value' => 'g2', 'label' => 'Grade 2'],
                                ['value' => 'g3', 'label' => 'Grade 3'],
                                ['value' => 'g4', 'label' => 'Grade 4'],
                                ['value' => 'g5', 'label' => 'Grade 5'],
                                ['value' => 'g6', 'label' => 'Grade 6'],
                                ['value' => 'g7', 'label' => 'Grade 7'],
                                ['value' => 'g8', 'label' => 'Grade 8'],
                                ['value' => 'g9', 'label' => 'Grade 9'],
                                ['value' => 'g10', 'label' => 'Grade 10'],
                                ['value' => 'g11', 'label' => 'Grade 11'],
                                ['value' => 'g12', 'label' => 'Grade 12'],
                            ],
                        ],
                        ['type' => 'textarea', 'key' => 'questions', 'label_key' => 'forms.inquiry.questions', 'settings' => ['rows' => 5], 'validation' => ['max_length' => 4000]],
                        ['type' => 'button', 'key' => 'submit', 'label_key' => 'forms.inquiry.submit', 'settings' => ['action' => 'submit', 'variant' => 'primary']],
                    ],
                ],
            ],
        ],
    ],

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
