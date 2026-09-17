<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use RuntimeException;

class RolesSeeder extends Seeder
{
    /*
     * The default role set.
     *
     * A role is named after a JOB rather than after a permission level, because
     * "Editor" tells an admin who to hand it to and "Level 2" does not. Nine of
     * them, and the split that matters is the first one: an ADMINISTRATOR runs
     * the school, an OWNER decides what a role means. Everything to do with
     * granting authority - editing a role, attaching a permission, minting or
     * rotating an API key - is owner-only, so an administrator cannot quietly
     * widen their own account. That is the same reasoning PermissionsSeeder
     * applies to API keys, one layer up.
     *
     * Definitions are written as MODULE PREFIXES, not as code lists. `articles`
     * means every articles.* code there is, so a module that grows a
     * `articles.duplicate` tomorrow lands in Editor without anyone remembering
     * to come back here. The cost is that a prefix typo would silently produce
     * an under-powered role, which assertPrefixes() below turns into a loud
     * failure instead.
     *
     * ------------------------------------------------------------------------
     * RE-RUNNING THIS SEEDER DOES NOT RE-APPLY A ROLE'S PERMISSIONS.
     *
     * A baseline is granted once, when the role row is CREATED. After that the
     * role belongs to the admin who has been tuning it, exactly as a seeded
     * form's fields belong to whoever edited them (FormsSeeder builds structure
     * only for a form it just created, and TranslationService::putMany defaults
     * to onlyMissing for the same reason). Reseeding a live install must never
     * hand somebody back a permission they deliberately revoked.
     *
     * OWNER IS THE ONE EXCEPTION and has to be: it is defined as "everything",
     * so it is re-synced on every run. Without that, every permission added
     * after the first seed - the 30 identity codes were the last batch - would
     * be held by nobody at all, and the fix (grant them by hand, in a UI that
     * needs owner rights to reach) would already be out of reach. To rebuild any
     * other role from its baseline, delete it and reseed.
     * ------------------------------------------------------------------------
     */

    /** Everything a content person owns. Media rides along - they upload into it. */
    private const CONTENT = [
        'pages', 'articles', 'albums', 'events', 'achievements', 'partners',
        'documents', 'banners', 'calendars', 'categories', 'brands',
        'brand-asset-groups', 'brand-assets', 'contact-details', 'programs',
        'streams', 'grades', 'media',
    ];

    /** Site structure. Separate from CONTENT: a typo here changes every page's chrome. */
    private const NAVIGATION = ['menus', 'menu-items'];

    private const FORMS = [
        'forms', 'form-fields', 'form-submissions', 'form-webhooks',
        'form-notification-groups', 'form-blocked-countries', 'form-blocked-ips',
    ];

    private const NEWSLETTERS = ['newsletters', 'newsletter-groups', 'newsletter-group-subscribers'];

    private const RECRUITMENT = [
        'job-offers', 'job-applications', 'candidates', 'candidate-matches',
        'candidate-clusters', 'clusters', 'job-offer-clusters',
    ];

    private const VISITS = ['visit-services', 'visit-slots', 'visit-reservations'];

    private const FACILITIES = [
        'facilities', 'facility-slots', 'facility-reservations',
        'facility-articles', 'facility-albums',
    ];

    private const LOCALISATION = ['languages', 'translations'];

    private const SYSTEM = [
        'settings', 'integrations', 'notification-groups',
        'notification-group-users', 'countries',
    ];

    /** The RBAC surface. Owner-only in full; an administrator gets the readable half. */
    private const ACCESS = [
        'users', 'user-roles', 'sessions', 'roles', 'role-permissions',
        'permissions', 'api-keys', 'api-key-permissions',
    ];

    /**
     * Seed the application's default roles.
     */
    public function run(): void
    {
        $permissions = Permission::pluck('id', 'code');

        if ($permissions->isEmpty()) {
            throw new RuntimeException('No permissions found - run PermissionsSeeder before RolesSeeder.');
        }

        $this->assertPrefixes($permissions->keys()->all());

        foreach ($this->roles() as $code => $definition) {
            /*
             * firstOrCreate, not updateOrCreate: `code` is the stable identity
             * (the same contract a menu's code carries), and `name` is a label an
             * admin is free to rewrite. Reseeding must not undo the rename.
             */
            $role = Role::firstOrCreate(['code' => $code], ['name' => $definition['name']]);

            if (! $role->wasRecentlyCreated && $code !== 'owner') {
                $this->command?->line("  <fg=gray>kept</> {$code} (already exists)");

                continue;
            }

            $codes = $this->codesFor($definition, $permissions->keys()->all());

            $role->syncPermissions($permissions->only($codes)->values()->all());

            $verb = $role->wasRecentlyCreated ? 'created' : 'resynced';
            $this->command?->line("  <fg=green>{$verb}</> {$code} ".count($codes).' permissions');
        }
    }

    /**
     * Expand one definition into the permission codes it grants.
     *
     * @param  array<string, mixed>  $definition
     * @param  array<int, string>  $all
     * @return array<int, string>
     */
    private function codesFor(array $definition, array $all): array
    {
        if ($definition['all'] ?? false) {
            $codes = $all;
        } else {
            $modules = $definition['modules'] ?? [];
            $reads = $definition['reads'] ?? [];

            $codes = array_filter($all, function (string $code) use ($modules, $reads) {
                [$prefix, $verb] = $this->split($code);

                return in_array($prefix, $modules, true)
                    || (in_array($prefix, $reads, true) && in_array($verb, ['index', 'show'], true));
            });

            $codes = array_merge($codes, $definition['codes'] ?? []);
        }

        $codes = array_diff($codes, $this->expandExcept($definition['except'] ?? [], $all));

        /*
         * A verb-level carve-out, applied AFTER the modules are expanded: "this
         * role does everything in these modules except delete". Listing the
         * codes instead would mean naming one per module and re-visiting all of
         * them the day a module is added - which is exactly the drift the
         * prefix-based definitions exist to avoid.
         */
        if ($verbs = $definition['exceptVerbs'] ?? []) {
            $codes = array_filter($codes, fn (string $code) => ! in_array($this->split($code)[1], $verbs, true));
        }

        /*
         * /admin itself is gated on dashboards.index, so a role without it signs
         * in and lands on a 403 with no way forward. Every role gets it.
         */
        $codes[] = 'dashboards.index';

        return array_values(array_unique($codes));
    }

    /**
     * `except` accepts a bare code, or `module.*` meaning every code in it.
     *
     * @param  array<int, string>  $except
     * @param  array<int, string>  $all
     * @return array<int, string>
     */
    private function expandExcept(array $except, array $all): array
    {
        $out = [];

        foreach ($except as $entry) {
            if (! str_ends_with($entry, '.*')) {
                $out[] = $entry;

                continue;
            }

            $prefix = substr($entry, 0, -2);

            foreach ($all as $code) {
                if ($this->split($code)[0] === $prefix) {
                    $out[] = $code;
                }
            }
        }

        return $out;
    }

    /** Split a code into its module prefix and its verb, on the LAST dot. */
    private function split(string $code): array
    {
        $at = strrpos($code, '.');

        return [substr($code, 0, $at), substr($code, $at + 1)];
    }

    /**
     * A prefix that matches no permission is a typo, and a typo here is silent:
     * the role simply comes out weaker than intended, which nobody notices until
     * somebody cannot do their job. Fail on it instead.
     *
     * @param  array<int, string>  $all
     */
    private function assertPrefixes(array $all): void
    {
        $known = array_unique(array_map(fn (string $code) => $this->split($code)[0], $all));

        $named = array_unique(array_merge(
            self::CONTENT, self::NAVIGATION, self::FORMS, self::NEWSLETTERS,
            self::RECRUITMENT, self::VISITS, self::FACILITIES, self::LOCALISATION,
            self::SYSTEM, self::ACCESS, ['dashboards', 'analytics', 'activities'],
        ));

        if ($unknown = array_diff($named, $known)) {
            throw new RuntimeException(
                'RolesSeeder names module prefixes that no permission uses: '.implode(', ', $unknown)
            );
        }

        /*
         * The reverse direction is a warning, not a failure: a brand-new module's
         * permissions exist before anybody decides which role should hold them,
         * and that is a legitimate state to seed in. Say so rather than block.
         */
        if ($missing = array_diff($known, $named)) {
            $this->command?->warn(
                '  Not in any role definition: '.implode(', ', $missing)
            );
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function roles(): array
    {
        $content = array_merge(self::CONTENT, self::NAVIGATION);

        return [
            /*
             * Everything, always. Re-synced on every run - see the note up top.
             */
            'owner' => [
                'name' => 'Owner',
                'all' => true,
            ],

            /*
             * Runs the school. Reads the whole RBAC graph and assigns EXISTING
             * roles, but cannot change what a role means or issue an API key -
             * otherwise "administrator" and "owner" are the same thing with two
             * names, since anyone who can edit a role can grant themselves the
             * rest of it.
             */
            'administrator' => [
                'name' => 'Administrator',
                'modules' => array_merge(
                    $content, self::FORMS, self::NEWSLETTERS, self::RECRUITMENT,
                    self::VISITS, self::FACILITIES, self::LOCALISATION, self::SYSTEM,
                    ['users', 'user-roles', 'sessions', 'activities', 'analytics'],
                ),
                'reads' => ['roles', 'role-permissions', 'permissions'],
                'except' => ['api-keys.*', 'api-key-permissions.*'],
            ],

            /*
             * The CMS role. Full run of the public site's content and its
             * navigation; nothing about people, forms or system config.
             */
            'editor' => [
                'name' => 'Editor',
                'modules' => $content,
                'codes' => ['activities.index'],
            ],

            /*
             * Writes and edits, never deletes and never touches navigation. The
             * point of the role is that a mistake is recoverable: an author can
             * get a page wrong, but cannot remove one or re-point the menu that
             * links to it.
             */
            'author' => [
                'name' => 'Author',
                'modules' => self::CONTENT,
                'reads' => self::NAVIGATION,
                'exceptVerbs' => ['destroy', 'detach'],
            ],

            /*
             * Reads the whole app and exports from it, writes nothing. Excludes
             * the RBAC surface - who holds which key is not an analytics
             * question - and candidates.cv, which PermissionsSeeder keeps apart
             * from candidates.show precisely so that reading a list of applicants
             * does not come with the right to pull their files.
             */
            'analyst' => [
                'name' => 'Analyst',
                'reads' => array_merge(
                    $content, self::FORMS, self::NEWSLETTERS, self::RECRUITMENT,
                    self::VISITS, self::FACILITIES, self::LOCALISATION, self::SYSTEM,
                ),
                'codes' => [
                    'analytics.index', 'activities.index',
                    'form-submissions.export', 'job-applications.export',
                    'visit-reservations.export', 'facility-reservations.export',
                ],
            ],

            /*
             * Hiring, end to end: postings, applicants, the typed profile tables,
             * the talent pools and the CV itself. Reads form submissions because
             * an application IS one, and the raw answers stay there.
             */
            'recruiter' => [
                'name' => 'Recruiter',
                'modules' => self::RECRUITMENT,
                'codes' => [
                    'candidates.cv', 'activities.index',
                    'form-submissions.index', 'form-submissions.show', 'form-submissions.export',
                ],
            ],

            /*
             * Reception. Both booking modules, because at a school this is one
             * person: the desk that answers "can we come and see the place?"
             * also answers "can we rent the hall?", and Visitor is a shared row
             * across the two by design.
             */
            'front-desk' => [
                'name' => 'Front Desk',
                'modules' => array_merge(self::VISITS, self::FACILITIES),
                'codes' => [
                    'form-submissions.index', 'form-submissions.show', 'form-submissions.export',
                ],
            ],

            /*
             * Forms and mailing lists. Reads content because a form is embedded
             * in a page and you cannot place one without seeing where it goes.
             */
            'marketing' => [
                'name' => 'Marketing',
                'modules' => array_merge(self::FORMS, self::NEWSLETTERS),
                'reads' => $content,
                'codes' => ['analytics.index'],
            ],

            /*
             * The localisation catalogue. Reads content so a string can be seen
             * in the context it renders in; editing the content itself is the
             * editor's job, not a side effect of holding the language list.
             */
            'translator' => [
                'name' => 'Translator',
                'modules' => self::LOCALISATION,
                'reads' => $content,
            ],
        ];
    }
}
