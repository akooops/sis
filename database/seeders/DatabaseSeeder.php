<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PermissionsSeeder::class);
        $this->call(InitAdminAccountSeeder::class);
        $this->call(LanguageTranslationSeeder::class);
        $this->call(SettingsSeeder::class);
        $this->call(PagesSeeder::class);
        $this->call(MenusSeeder::class);
    }
}
