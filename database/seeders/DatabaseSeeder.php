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

        /*
         * DEV FIXTURES ONLY. Everything above ships with the app; everything here
         * is scaffolding so a fresh checkout is legible — a fixture rather than a
         * second opinion about what the school should contain. Every one of these
         * is firstOrCreate throughout and safe to run standalone with
         * `--class=`, and none of them is is_system, so an admin can delete the
         * lot without breaking a page.
         */
        if (app()->environment('local')) {
            $this->call([
                DemoHrSeeder::class,
                DemoVisitsSeeder::class,
                DemoFacilitiesSeeder::class,
            ]);
        }

        if (app()->environment('production')) {

        }
    }
}
