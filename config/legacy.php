<?php

/*
 * THE LEGACY IMPORT'S MAPPING SURFACE.
 *
 * Everywhere the old app and this one disagree about a NAME — a menu's code, a
 * settings key, an enum value, a class in a polymorphic column — the answer is
 * written here rather than buried in an importer, for the same reason
 * config/jobs.php exists: the old schema is frozen, so a constant is honest, and
 * a correction is one line in one file rather than a hunt through a service.
 *
 * WHAT IS NOT HERE: anything that is a straight column-to-column copy. An
 * importer spells its own columns out, because reading that importer should tell
 * you what it writes without a second file open beside it.
 */

return [

    /*
     * The connection name the importers read through. Registered at runtime by
     * App\Services\Legacy\LegacyDatabase from --legacy / LEGACY_DB_DATABASE, so
     * config/database.php stays a description of THIS app only.
     */
    'connection' => 'legacy',

    'database' => [
        'host' => env('LEGACY_DB_HOST', env('DB_HOST', '127.0.0.1')),
        'port' => env('LEGACY_DB_PORT', env('DB_PORT', '3306')),
        'database' => env('LEGACY_DB_DATABASE'),
        'username' => env('LEGACY_DB_USERNAME', env('DB_USERNAME', 'root')),
        'password' => env('LEGACY_DB_PASSWORD', env('DB_PASSWORD', '')),
    ],

    /*
     * Where the old app's uploaded files were handed to us.
     *
     * The old FileService wrote every upload to the `public` disk as
     * `uploads/{uuid}.{ext}`, so this should point at either that
     * `storage/app/public` directory or the `uploads` folder inside it — the
     * importer accepts both and says which one it resolved.
     */
    'files' => env('LEGACY_FILES_PATH'),

    /*
     * A locale the old `translations` table used that this app spells
     * differently. Empty today — both apps use plain two-letter codes — and kept
     * because the alternative is an importer quietly dropping a language.
     */
    'locales' => [
        // 'en-GB' => 'en',
    ],

    /*
     * LEGACY MENU NAME => THIS APP'S MENU CODE.
     *
     * The old menus table had no code at all; a menu was addressed by its
     * auto-increment id and its seeder gave it a machine-ish `name`. This app
     * addresses a menu BY CODE and the codes are frozen (SiteContext reads
     * `footer_primary` and friends by name), so a legacy menu has to be told
     * which one it is or the site would render an empty header.
     *
     * A legacy menu whose name is not listed keeps its own slugified name as its
     * code and is imported as a non-system menu — an admin-created menu, which
     * is exactly what an unlisted one is.
     */
    'menus' => [
        'header_primary_menu' => 'header_primary',
        'footer_primary_menu' => 'footer_primary',
        'cta_menu' => 'header_cta',
        'services_menu' => 'header_services',
    ],

    /*
     * LEGACY POLYMORPHIC CLASS => THIS APP'S CLASS.
     *
     * `menu_items.linkable_type` stores fully-qualified class names. Most survive
     * the move unchanged because both apps namespace models the same way, so only
     * the renames are listed — anything absent maps to itself, and anything that
     * maps to null is a type this app dropped, whose link becomes a plain label.
     */
    'morphs' => [
        'App\Models\JobPosting' => App\Models\JobOffer::class,
        'App\Models\ProgramStream' => App\Models\Stream::class,

        /*
         * A legacy "newsletter" was a downloadable PDF, which is a Document here.
         * This app's Newsletter is an email campaign — a different thing wearing
         * the same word, and the one rename most likely to be got wrong.
         */
        'App\Models\Newsletter' => App\Models\Document::class,

        'App\Models\AchievementCategory' => App\Models\Category::class,
        'App\Models\Nationality' => App\Models\Country::class,

        /*
         * AND THE ONE THAT LOOKS LIKE IT SURVIVES UNCHANGED BUT MUST NOT.
         *
         * The old `forms` table was a list of DOWNLOADS — school contracts,
         * summer packs, timetables — each a name and one attached file. This
         * app's `forms` is a builder with pages, fields, spam guards and
         * submissions, plus four seeded `is_system` rows the site resolves by
         * slug. Mapping one onto the other would create fifteen unsubmittable
         * forms; the shape that actually matches is Document.
         */
        'App\Models\Form' => App\Models\Document::class,
    ],

    /*
     * FREE-TEXT NATIONALITY => ISO CODE, for answers the countries table cannot
     * match on its own.
     *
     * GROWN BY RUNNING THE IMPORT AND READING THE REPORT, which names every
     * unmatched value and how many applications used it. It is worth the trouble
     * because `candidates.country_id` feeds the scorer's reasoning about work
     * authorisation — an unresolved nationality is a candidate mis-scored, not
     * merely a blank field.
     *
     * Keys are matched AFTER folding (lowercased, Arabic orthography normalised),
     * so `الأردن` and `الاردن` are the same key and need only one entry.
     *
     * Three kinds of miss show up, and only the first two are safe to map:
     *   - EXONYM DRIFT. The table says `Türkiye`; applicants wrote `Turkey`.
     *   - GENDER AND SPELLING. The table carries the masculine demonym `سعودي`;
     *     applicants wrote `سعودية`.
     *   - GENUINELY AMBIGUOUS. `Filipino Married to Saudi`, `Australia and New
     *     Zealand` — two countries in one answer. These are deliberately absent:
     *     a guess here is worse than the blank, because nothing downstream would
     *     ever show it was a guess.
     */
    'nationalities' => [
        'turkey' => 'TR',
        'turkiye' => 'TR',
        'سعوديه' => 'SA',
        'egypt-ion' => 'EG',
        'pakistan kpk' => 'PK',
        'pakistanis' => 'PK',
    ],

    /*
     * LEGACY ENUM VALUE => THIS APP'S.
     *
     * Only the values that actually differ. A value not listed is passed through,
     * and one that matches neither is reported and left at the column default.
     */
    'enums' => [
        'employment_type' => [
            'internship' => 'intern',
        ],
    ],

    /*
     * LEGACY `settings` ROW => THIS APP'S SETTING PATH.
     *
     * This app deliberately has SEVEN settings (see config/settings.php): anything
     * a visitor reads is a lang key and anything with one right answer is a
     * constant. So most of the old settings table has no destination, and the
     * importer reports each unmapped key rather than dropping it in silence.
     *
     * `code.*` carries raw markup and comes across verbatim. `pathway_program_id`
     * is a legacy integer id and is resolved through the import map to the ULID
     * the programme became.
     */
    'settings' => [
        'code.head_code' => 'code.head',
        'code.foot_code' => 'code.foot',
        'code.support_button_code' => 'code.support_button',
        'pathway.pathway_program_id' => 'homepage.pathway_program',
    ],

    /*
     * LEGACY `settings` ROW => A contact_details ROW.
     *
     * The old app kept the school's phone numbers, addresses and social links in
     * the settings table; this one has a table for them, because a contact is a
     * repeatable thing with an order and a translated label, which a single
     * settings value cannot express.
     *
     * `multi` marks a legacy value holding SEVERAL entries — the old admin stored
     * those as a JSON array or as newline-separated text — so one settings row
     * becomes several contact rows.
     */
    'contacts' => [
        'contact.emails' => ['type' => 'email', 'name' => 'Email', 'multi' => true],
        'contact.phones' => ['type' => 'phone', 'name' => 'Phone', 'multi' => true],
        'contact.address' => ['type' => 'address', 'name' => 'Address', 'multi' => false],
        'social.social_facebook_url' => ['type' => 'social', 'name' => 'Facebook', 'platform' => 'facebook'],
        'social.social_instagram_url' => ['type' => 'social', 'name' => 'Instagram', 'platform' => 'instagram'],
        'social.social_twitter_url' => ['type' => 'social', 'name' => 'X', 'platform' => 'twitter'],
        'social.social_youtube_url' => ['type' => 'social', 'name' => 'YouTube', 'platform' => 'youtube'],
        'social.social_linkedin_url' => ['type' => 'social', 'name' => 'LinkedIn', 'platform' => 'linkedin'],
        'social.social_snapchat_url' => ['type' => 'social', 'name' => 'Snapchat', 'platform' => 'snapchat'],
    ],

    /*
     * The map URL setting, folded ONTO the address row rather than becoming a row
     * of its own — contact_details carries `map_url` as a column on the address.
     */
    'map_setting' => 'google.google_maps_url',

    /*
     * LEGACY TABLE => THE FORM ITS ROWS ARE SUBMISSIONS OF, and the answer key
     * each legacy column becomes.
     *
     * The old app gave contact messages and admissions inquiries a typed table
     * each; here they are what they always were — submissions of the seeded
     * `contact` and `inquiries` system forms — so they come across as
     * FormSubmission rows and are read under Forms → Submissions.
     *
     * The keys on the right are the SEEDED FIELD KEYS, frozen because both forms
     * are `is_system`.
     */
    'submissions' => [
        'contact_submissions' => [
            'form' => 'contact',
            'fields' => [
                'name' => 'name',
                'email' => 'email',
                'phone' => 'phone',
                'subject' => 'subject',
                'message' => 'message',
            ],
        ],
        'inquiries' => [
            'form' => 'inquiries',
            'fields' => [
                'guardian_name' => 'guardian_name',
                'email' => 'email',
                'phone' => 'phone',
                'student_name' => 'student_name',
                'student_birthdate' => 'student_birthdate',
                'student_school' => 'student_school',
                'academic_year_applied' => 'academic_year',
                'grade_applied' => 'grade',
                'questions' => 'questions',
            ],
        ],
    ],

    /*
     * Columns the old app carried that this one has nowhere to put.
     *
     * Listed so the import can REPORT them per module instead of dropping them in
     * silence — the difference between "we decided not to carry the facility
     * theme" and "nobody noticed the facility theme was gone".
     */
    'dropped' => [
        'facilities' => ['domain', 'theme', 'logo_file_id', 'email', 'phone', 'whatsapp', 'socials', 'tagline', 'address'],
        'brands' => ['order', 'tagline'],
        'job_postings' => ['number_of_positions'],
        'job_applications' => ['ai_score', 'ai_score_explanation', 'ai_score_status', 'ai_scored_at'],
        'facility_reservations' => ['guests_count'],
        'programs' => ['has_streams'],
    ],
];
