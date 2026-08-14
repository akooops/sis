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
     */    /*
     * The setting catalogue. SettingsSeeder mirrors it into the settings table;
     * an admin then edits `value` and nothing else, which is why the module has
     * no create and no delete. Config rather than a CRUD table because a setting
     * exists only for the code that reads it by key — a row an admin invented
     * would be one nothing consults. Grown by code: add an entry here, reseed.
     *
     * DELIBERATELY SHORT. A setting is for something with ONE value across every
     * language and every page. Anything a visitor READS is a lang key instead —
     * a settings row would serve the English string on the Arabic site — and
     * anything with exactly one right answer is a constant. What survived that
     * test is below; everything else was removed rather than left inert.
     *
     * The menus are the clearest case: they used to be four `model` settings
     * pointing at a Menu, and they are now resolved by `code` in
     * SiteContext::MENU_SLOTS. A system menu's code cannot be changed
     * (MenusController blocks it) — so the code IS the stable reference the
     * setting was trying to provide, and the setting was a second way to say the
     * same thing that could disagree with the first.
     *
     * `type` is one of Setting::TYPES and names the SHAPE of `value`:
     *   text   -> a string            number -> numeric          date -> a date
     *   select -> one of `options`    model  -> the id of a `model` record
     *
     * `filter` narrows a model setting to a subset of its model. Its keys are
     * filters the target's index endpoint already exposes, because the picker
     * sends them there as filter[key]; the `filters` map beside that model above
     * says how the server applies the same narrowing when it validates.
     *
     * `default` seeds `value` on the run that CREATES the row and never again —
     * see SettingsSeeder, whose whole reason for existing is that reseeding must
     * not reset what an admin configured.
     */
    'settings' => [

        /*
         * Raw markup injected into every public page.
         *
         * STORED XSS BY DESIGN. The values render through {!! !!} and are not
         * sanitised, because the whole point is to paste a verification meta tag
         * or a third-party widget snippet in verbatim — anything that escaped it
         * would also break it. The only gate is admin access, so keep this group
         * off any role that is not fully trusted.
         */
        'code' => [
            'head' => [
                'name' => 'Head code',
                'type' => 'text',
                'description' => 'Markup injected into the <head> of every public page. Verification tags, third-party snippets.',
                'sort' => 1,
            ],
            'foot' => [
                'name' => 'Footer code',
                'type' => 'text',
                'description' => 'Markup injected just before </body> on every public page.',
                'sort' => 2,
            ],
        ],

        'homepage' => [
            'banner_mode' => [
                'name' => 'Banner mode',
                'type' => 'select',
                'options' => [
                    ['value' => 'ordered', 'label' => 'All banners, in order'],
                    ['value' => 'random', 'label' => 'One at random'],
                ],
                'default' => 'ordered',
                'description' => 'Whether the hero runs every live banner as a slider, or shows a single one picked at random on each visit.',
                'sort' => 1,
            ],
            'pathway_program' => [
                'name' => 'Pathway programme',
                'type' => 'model',
                'model' => 'program',
                'description' => 'The programme whose streams are shown as the pathway cards on the homepage.',
                'sort' => 2,
            ],
        ],

        /*
         * Which integration answers when nothing more specific is pinned.
         *
         * Every channel already falls back to Integration::activeFor($type),
         * which returns the first ENABLED integration of a type — fine with one,
         * arbitrary with two. These settings are how an admin says which.
         */
        'integrations' => [
            'analytics' => [
                'name' => 'Site analytics',
                'type' => 'model',
                'model' => 'integration',
                'filter' => ['type' => 'analytics'],
                'description' => 'The analytics property every public page reports to, forms included. There is one property for the whole site — a form cannot pin its own.',
                'sort' => 1,
            ],
            'ai' => [
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
