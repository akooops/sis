<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    /**
     * Seed the application's permissions.
     */
    public function run(): void
    {
        $permissions = [
            // Index permissions that gate the admin page shells (routes/web.php).
            ['code' => 'dashboards.index', 'name' => 'View dashboard', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'users.index', 'name' => 'View users', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'roles.index', 'name' => 'View roles', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'permissions.index', 'name' => 'View permissions', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'api-keys.index', 'name' => 'View API keys', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'media.index', 'name' => 'View media', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'activities.index', 'name' => 'View activity log', 'supports_web' => true, 'supports_api' => true],

            ['code' => 'integrations.index', 'name' => 'View integrations', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'integrations.store', 'name' => 'Create integrations', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'integrations.update', 'name' => 'Update integrations', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'integrations.destroy', 'name' => 'Delete integrations', 'supports_web' => true, 'supports_api' => false],

            // Notification groups + members (the inbox itself is auth-only, no permission).
            ['code' => 'notification-groups.index', 'name' => 'View notification groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'notification-groups.store', 'name' => 'Create notification groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'notification-groups.update', 'name' => 'Update notification groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'notification-groups.destroy', 'name' => 'Delete notification groups', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'notification-group-users.index', 'name' => 'View group members', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'notification-group-users.store', 'name' => 'Add group members', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'notification-group-users.destroy', 'name' => 'Remove group members', 'supports_web' => true, 'supports_api' => false],

            // Languages + translations. The read-only key registry is gated by
            // translations.index — it has no codes of its own.
            ['code' => 'languages.index', 'name' => 'View languages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'languages.store', 'name' => 'Create languages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'languages.update', 'name' => 'Update languages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'languages.destroy', 'name' => 'Delete languages', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'translations.index', 'name' => 'View translations', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'translations.update', 'name' => 'Update translations', 'supports_web' => true, 'supports_api' => false],

            // Content pages.
            ['code' => 'pages.index', 'name' => 'View pages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'pages.store', 'name' => 'Create pages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'pages.update', 'name' => 'Update pages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'pages.destroy', 'name' => 'Delete pages', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'articles.index', 'name' => 'View articles', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'articles.store', 'name' => 'Create articles', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'articles.update', 'name' => 'Update articles', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'articles.destroy', 'name' => 'Delete articles', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'albums.index', 'name' => 'View albums', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'albums.store', 'name' => 'Create albums', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'albums.update', 'name' => 'Update albums', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'albums.destroy', 'name' => 'Delete albums', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'events.index', 'name' => 'View events', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'events.store', 'name' => 'Create events', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'events.update', 'name' => 'Update events', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'events.destroy', 'name' => 'Delete events', 'supports_web' => true, 'supports_api' => false],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                [
                    'code' => $permission['code'],
                ],
                [
                    'name' => $permission['name'],
                    'supports_web' => $permission['supports_web'],
                    'supports_api' => $permission['supports_api'],
                ]
            );
        }
    }
}
