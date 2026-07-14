<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Seed the application's roles.
     */
    public function run(): void
    {
        $ownerRole = Role::updateOrCreate(
            ['name' => 'Owner', 'code' => 'owner'],
            ['is_default' => false],
        );

        $ownerRole->syncPermissions(Permission::pluck('id')->all());
    }
}
