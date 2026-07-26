<?php

return [

    /*
     * The translation key registry — every line the app can translate. This array
     * is the source of truth; TranslationKeysSeeder mirrors the (group, key)
     * pairs into the translation_keys table and writes the English value to
     * lang/en/{group}.php. Grown by code, never CRUD: add a line here, reseed.
     *
     * The outer key is the GROUP (the lang file's basename, so `common` is
     * lang/{locale}/common.php); the inner key is the dotted path inside it and
     * the value is the English source string. Keys are declared FLAT-DOTTED here
     * but written NESTED to disk, which is what __() and @lang() read:
     *
     *     'confirm.title' => 'Are you sure?'   ->   ['confirm' => ['title' => …]]
     *     @lang('common.confirm.title')
     */
    'keys' => [
        'common' => [
            'save' => 'Save',
            'cancel' => 'Cancel',
            'delete' => 'Delete',
            'edit' => 'Edit',
            'create' => 'Create',
            'search' => 'Search',
            'actions' => 'Actions',
            'yes' => 'Yes',
            'no' => 'No',
            'confirm.title' => 'Are you sure?',
            'confirm.body' => 'This action cannot be undone.',
        ],
    ],

    /*
     * A locale code and a group both become path segments, so anything not
     * matching these never reaches the disk — see TranslationService::guard().
     */
    'code_pattern' => '/^[a-z]{2}(_[A-Z]{2})?$/',
    'group_pattern' => '/^[a-z0-9_-]+$/',

    /*
     * On write, a registry key absent from the resulting array is written back as
     * an empty string rather than dropped, so a lang file always mirrors the
     * registry and "missing" means one thing everywhere.
     *
     * The trade-off: Laravel falls back to fallback_locale only when a key is
     * ABSENT, not when it is ''. Set this false to leave missing keys out and
     * keep that fallback instead.
     */
    'fill_missing_keys' => true,
];
