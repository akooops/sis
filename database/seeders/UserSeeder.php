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

        /*
         * By CODE. `name` is 'Owner' and an admin may rewrite it at will, so this
         * matched only because MySQL's default collation is case-insensitive -
         * on a case-sensitive one it finds nothing, the super user is seeded with
         * no roles at all, and the first sign-in 403s on the dashboard with no
         * way to grant anything back.
         */
        $owner = Role::where('code', 'owner')->first();

        if ($owner) {
            $user->userRoles()->firstOrCreate(['role_id' => $owner->id]);
        }
    }
}
