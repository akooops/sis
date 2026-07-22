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

            // Notifications management (the inbox itself is auth-only, no permission).
            ['code' => 'notifications.index', 'name' => 'View sent notifications', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'notifications.store', 'name' => 'Send notifications', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'notifications.destroy', 'name' => 'Delete notifications', 'supports_web' => true, 'supports_api' => true],

            ['code' => 'notification-groups.index', 'name' => 'View notification groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'notification-groups.store', 'name' => 'Create notification groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'notification-groups.update', 'name' => 'Update notification groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'notification-groups.destroy', 'name' => 'Delete notification groups', 'supports_web' => true, 'supports_api' => false],
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
