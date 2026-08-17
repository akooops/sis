<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,
            RolesSeeder::class,
            UserSeeder::class,
            IntegrationTypesSeeder::class,
            IntegrationDriversSeeder::class,
            NotificationTypesSeeder::class,
            LanguagesSeeder::class,
            TranslationKeysSeeder::class,
            CategoriesSeeder::class,
            CountriesSeeder::class,
            NewsletterGroupsSeeder::class,
            PagesSeeder::class,
            MenusSeeder::class,
            FormsSeeder::class,
            JobOffersSeeder::class,
            SettingsSeeder::class,
        ]);

        if (app()->environment('local')) {

        }

        if (app()->environment('production')) {

        }
    }
}
