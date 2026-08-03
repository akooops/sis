<?php

return [

    /*
     * What a `model`-typed setting may point at, keyed by the App\Enums\MorphType
     * alias that lands in settings.model_type. A closed list rather than "any
     * model": `value` holds a bare id, so the picker needs an endpoint to load
     * and a field to show, and the rule that validates the id needs a table to
     * check against — Setting::linkableTable(), the same call MenuItem makes.
     *
     * `resource` is the API ROUTE NAME the form's remote Select loads from and
     * `label` the field it renders. Both are resolved at runtime, so a wrong one
     * is a picker that silently loads nothing rather than an error: check the
     * route exists in routes/api.php and the alias in MorphType before adding a
     * row here.
     *
     * `filters` is what lets a setting narrow its picker — "an AI integration",
     * not "any integration". Each key is a filter the TARGET CONTROLLER already
     * exposes in its allowedFilters, because the form sends it there verbatim as
     * filter[key]; the value beside it says how the SERVER applies the same
     * narrowing when it validates the submitted id, either through a model
     * `scope` or a plain `column`. Both halves read one array on the setting row,
     * so the picker cannot offer what the validator would reject.
     *
     * A model with no `filters` entry simply cannot be narrowed, and a setting
     * that tries fails closed — every id rejected — rather than silently widening.
     * `media` is the notable one: its `type` filter is an inline controller
     * callback with no model scope behind it, so there is nothing to mirror
     * without duplicating the logic. Give Media a scope and it can join in.
     */
    'models' => [
        'media' => ['name' => 'Media', 'resource' => 'api.v1.admin.media.index', 'label' => 'name'],
        'page' => ['name' => 'Page', 'resource' => 'api.v1.admin.pages.index', 'label' => 'name', 'filters' => [
            'status' => ['column' => 'status'],
            'menu_id' => ['column' => 'menu_id'],
            'is_system' => ['column' => 'is_system'],
        ]],
        'article' => ['name' => 'Article', 'resource' => 'api.v1.admin.articles.index', 'label' => 'name', 'filters' => [
            'status' => ['column' => 'status'],
            'category_id' => ['column' => 'category_id'],
        ]],
        'album' => ['name' => 'Album', 'resource' => 'api.v1.admin.albums.index', 'label' => 'name', 'filters' => [
            'status' => ['column' => 'status'],
        ]],
        'achievement' => ['name' => 'Achievement', 'resource' => 'api.v1.admin.achievements.index', 'label' => 'name', 'filters' => [
            'status' => ['column' => 'status'],
            'category_id' => ['column' => 'category_id'],
        ]],
        'event' => ['name' => 'Event', 'resource' => 'api.v1.admin.events.index', 'label' => 'name', 'filters' => [
            'status' => ['column' => 'status'],
        ]],
        'banner' => ['name' => 'Banner', 'resource' => 'api.v1.admin.banners.index', 'label' => 'name', 'filters' => [
            'status' => ['column' => 'status'],
        ]],
        'document' => ['name' => 'Document', 'resource' => 'api.v1.admin.documents.index', 'label' => 'name'],
        'menu' => ['name' => 'Menu', 'resource' => 'api.v1.admin.menus.index', 'label' => 'name', 'filters' => [
            'is_system' => ['column' => 'is_system'],
        ]],
        'form' => ['name' => 'Form', 'resource' => 'api.v1.admin.forms.index', 'label' => 'name', 'filters' => [
            'status' => ['column' => 'status'],
            'category_id' => ['column' => 'category_id'],
            'is_system' => ['column' => 'is_system'],
        ]],
        'brand' => ['name' => 'Brand', 'resource' => 'api.v1.admin.brands.index', 'label' => 'name', 'filters' => [
            'status' => ['column' => 'status'],
        ]],
        'category' => ['name' => 'Category', 'resource' => 'api.v1.admin.categories.index', 'label' => 'name', 'filters' => [
            'is_default' => ['column' => 'is_default'],
        ]],
        'program' => ['name' => 'Program', 'resource' => 'api.v1.admin.programs.index', 'label' => 'name'],
        // `type` goes through Integration::scopeOfType(), which is exactly what
        // the controller's own `type` filter calls — one implementation, so a
        // slot narrowed to "ai" means the same thing on both sides of the wire.
        'integration' => ['name' => 'Integration', 'resource' => 'api.v1.admin.integrations.index', 'label' => 'name', 'filters' => [
            'type' => ['scope' => 'ofType'],
            'is_enabled' => ['column' => 'is_enabled'],
        ]],
        'notification_group' => ['name' => 'Notification group', 'resource' => 'api.v1.admin.notification-groups.index', 'label' => 'name', 'filters' => [
            'integration_id' => ['column' => 'integration_id'],
        ]],
        'contact_detail' => ['name' => 'Contact detail', 'resource' => 'api.v1.admin.contact-details.index', 'label' => 'name', 'filters' => [
            'type' => ['column' => 'type'],
        ]],
    ],

    /*
     * The setting catalogue. SettingsSeeder mirrors it into the settings table;
     * an admin then edits `value` and nothing else, which is why the module has
     * no create and no delete. Config rather than a CRUD table because a setting
     * exists only for the code that reads it by key — a row an admin invented
     * would be one nothing consults. Grown by code: add an entry here, reseed.
     *
     * The outer key is the GROUP (the page's filter and the section a setting
     * renders under); the inner key is settings.key, unique within its group and
     * what application code reads a setting by.
     *
     * `type` is one of Setting::TYPES and names the SHAPE of `value`:
     *   text   -> a string            number -> numeric          date -> a date
     *   select -> one of `options`    model  -> the id of a `model` record
     * `is_multiple` multiplies whichever type it is, so a list of strings is
     * text+is_multiple and a list of references is model+is_multiple. One flag
     * instead of five more type codes: the form repeats one control rather than
     * growing a second parallel branch for every shape.
     *
     * `filter` narrows a model setting to a subset of its model — an AI provider
     * slot offers AI integrations only. Its keys are filters the target's index
     * endpoint already exposes, because the picker sends them there as filter[key];
     * the `filters` map beside that model above says how the server applies the
     * same narrowing when it validates. Declaring it once is the point: a picker
     * and a validator that each carry their own copy agree until someone edits one.
     *
     * `default` seeds `value` the first time a row is created and never again —
     * see SettingsSeeder, whose whole reason for existing is that reseeding must
     * not reset what an admin configured. `sort` orders the settings inside
     * their group; `description` is the hint shown under the field.
     */
    'settings' => [

        'general' => [
            'site_name' => [
                'name' => 'Site name',
                'type' => 'text',
                'default' => 'Saud International Schools',
                'description' => 'The school name, used in page titles, outgoing email and the public site header.',
                'sort' => 1,
            ],
            'tagline' => [
                'name' => 'Tagline',
                'type' => 'text',
                'default' => 'Learning without limits',
                'description' => 'The single line shown under the school name.',
                'sort' => 2,
            ],
            'logo' => [
                'name' => 'Logo',
                'type' => 'model',
                'model' => 'media',
                'description' => 'The mark the public site header renders.',
                'sort' => 3,
            ],
            'favicon' => [
                'name' => 'Favicon',
                'type' => 'model',
                'model' => 'media',
                'description' => 'The small square icon browsers show in the tab.',
                'sort' => 4,
            ],
            'main_menu' => [
                'name' => 'Main menu',
                'type' => 'model',
                'model' => 'menu',
                'description' => 'Which menu the public site renders as its primary navigation.',
                'sort' => 5,
            ],
            'timezone' => [
                'name' => 'Timezone',
                'type' => 'select',
                /*
                 * A short list rather than the several hundred PHP zone ids: this
                 * is a select, not a search, and every zone offered is one the
                 * school actually operates in. Add a row when that changes.
                 */
                'options' => [
                    ['value' => 'Asia/Riyadh', 'label' => 'Riyadh (GMT+3)'],
                    ['value' => 'Asia/Dubai', 'label' => 'Dubai (GMT+4)'],
                    ['value' => 'Africa/Cairo', 'label' => 'Cairo (GMT+2)'],
                    ['value' => 'Europe/London', 'label' => 'London (GMT+0/+1)'],
                    ['value' => 'UTC', 'label' => 'UTC'],
                ],
                'default' => 'Asia/Riyadh',
                'description' => 'The zone dates and times are displayed in.',
                'sort' => 6,
            ],
            'items_per_page' => [
                'name' => 'Items per page',
                'type' => 'number',
                'default' => 12,
                'description' => 'How many records a public listing shows before paginating.',
                'sort' => 7,
            ],
        ],

        'seo' => [
            'meta_title' => [
                'name' => 'Meta title',
                'type' => 'text',
                'default' => 'Saud International Schools',
                'description' => 'The title search engines and social cards fall back to.',
                'sort' => 1,
            ],
            'meta_description' => [
                'name' => 'Meta description',
                'type' => 'text',
                'default' => 'An international school community built on curiosity, care and high expectations.',
                'description' => 'The summary shown under the title in search results.',
                'sort' => 2,
            ],
            'keywords' => [
                'name' => 'Keywords',
                'type' => 'text',
                'is_multiple' => true,
                'default' => ['saud international schools', 'international school', 'admissions'],
                'description' => 'Search keywords, one per entry.',
                'sort' => 3,
            ],
        ],

        'homepage' => [
            'hero_banner' => [
                'name' => 'Hero banner',
                'type' => 'model',
                'model' => 'banner',
                'description' => 'The banner shown at the top of the homepage.',
                'sort' => 1,
            ],
            'featured_articles' => [
                'name' => 'Featured articles',
                'type' => 'model',
                'model' => 'article',
                'is_multiple' => true,
                'default' => [],
                'description' => 'The articles pinned to the homepage, in the order chosen here.',
                'sort' => 2,
            ],
            'articles_per_page' => [
                'name' => 'Articles per page',
                'type' => 'number',
                'default' => 6,
                'description' => 'How many articles the homepage news section lists.',
                'sort' => 3,
            ],
        ],

        'academic' => [
            /*
             * No defaults: these are set at the start of each academic year, and a
             * seeded date would read as fact on a fresh install rather than as an
             * unanswered question.
             */
            'year_start' => [
                'name' => 'Academic year start',
                'type' => 'date',
                'description' => 'The first day of the current academic year.',
                'sort' => 1,
            ],
            'year_end' => [
                'name' => 'Academic year end',
                'type' => 'date',
                'description' => 'The last day of the current academic year.',
                'sort' => 2,
            ],
        ],

        'notifications' => [
            'default_email_integration' => [
                'name' => 'Default email integration',
                'type' => 'model',
                'model' => 'integration',
                // Without this the picker would offer SMS and AI integrations for
                // a slot that can only ever send mail.
                'filter' => ['type' => 'email'],
                'description' => 'Which email integration sends mail when nothing more specific is configured.',
                'sort' => 1,
            ],
            'default_ai_integration' => [
                'name' => 'Default AI provider',
                'type' => 'model',
                'model' => 'integration',
                'filter' => ['type' => 'ai'],
                'description' => 'Which AI integration answers when nothing more specific is configured.',
                'sort' => 2,
            ],
        ],
    ],
];
