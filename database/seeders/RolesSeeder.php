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
            ['code' => 'owner'],
            ['name' => 'Owner'],
        );

        $ownerRole->syncPermissions(Permission::pluck('id')->all());
    }
}
