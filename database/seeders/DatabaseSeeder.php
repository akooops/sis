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
            FormsSeeder::class,
            // Last: a setting's default may name another record (a menu, a page,
            // an integration), and a default only lands on the run that creates
            // the row — so anything it could point at must already exist.
            SettingsSeeder::class,
        ]);

        if (app()->environment('local')) {

        }

        if (app()->environment('production')) {

        }
    }
}
