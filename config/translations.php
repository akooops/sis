<?php

return [

    /*
     * The translation catalogue's GROUPS. Each is a lang file basename, so
     * `common` is lang/{locale}/common.php and __('nav.services') reads the
     * `nav` group below.
     *
     * Listed explicitly rather than derived from the catalogue, so a group can
     * be retired without its lines disappearing from the seeder, and so the seed
     * order is deterministic.
     */
    'groups' => ['common', 'nav', 'site', 'forms', 'jobs', 'visits', 'facilities'],

    /*
     * THE CATALOGUE ITSELF IS NOT HERE — it lives in TranslationKeysSeeder.
     *
     * This file is settings: which groups exist, what a locale code and a group
     * may look like as path segments, and whether a missing key is written back
     * as ''. The ~1,700 lines of locale => string maps that used to sit under a
     * `keys` entry are seed content — read once, at deploy time, by one seeder —
     * and the running app never touched them: __() and @lang() read lang/, which
     * the seeder writes. Leaving them here made `config:cache` serialise the
     * whole catalogue into every request's bootstrap.
     */

    /*
     * A locale code and a group both become path segments, so anything not
     * matching these never reaches the disk — see TranslationService::guard().
     */
    'code_pattern' => '/^[a-z]{2}(_[A-Z]{2})?$/',
    'group_pattern' => '/^[a-z0-9_-]+$/',

    /*
     * FALSE, and the reason matters.
     *
     * With true, a registered key absent from a file is written back as '' so
     * the file always mirrors the registry. But Laravel falls back to
     * fallback_locale only when a key is ABSENT, never when it is '' — so on a
     * public site a half-translated locale would render blank headings and blank
     * buttons to real visitors. English text always beats nothing.
     *
     * The missing-key report is unaffected: TranslationService::isTranslated()
     * already treats null and '' alike, and missingKeyIds() is built on it.
     *
     * The cost: an admin who deliberately CLEARS a translation no longer has
     * that stick across a reseed — but clearing now produces the English
     * fallback, which is what clearing was trying to express anyway.
     */
    'fill_missing_keys' => false,
];
