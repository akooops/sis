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

        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                [
                    'code' => $permission['code']
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
