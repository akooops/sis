<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class InitAdminAccountSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if(!User::where('email', 'ilyes24.azzi@gmail.com')->exists()){
            $user = User::create([
                'firstname' => 'Ilyes',
                'lastname' => 'AZZI',
                'username' => 'ilyes24',
                'email' => 'ilyes24.azzi@gmail.com',
                'email_verified_at' => now(),
                'password' => 'ilyes24',
            ]);
        }

        $ownerRole = Role::where('name', 'Super Admin')->first();
        $user = User::where('email', 'ilyes24.azzi@gmail.com')->first();

        if($ownerRole && $user){
            $user->roles()->sync([$ownerRole->id]);
        }
    }
}
