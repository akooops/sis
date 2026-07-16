<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\States\User\Approved;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's super user and grant it the owner role.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'ilyes24.azzi@gmail.com'],
            [
                'firstname' => 'Ilyes',
                'lastname' => 'Azzi',
                'username' => 'ilyes',
                'password' => 'ilyes123456',
                'status' => Approved::class,
            ],
        );

        $owner = Role::where('name', 'owner')->first();

        if ($owner) {
            $user->userRoles()->firstOrCreate(['role_id' => $owner->id]);
        }
    }
}
