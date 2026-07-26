<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

/**
 * The locales the app ships knowing about. English and Arabic are enabled; the
 * rest are seeded disabled so they can be turned on without a migration when the
 * translations are ready.
 *
 * updateOrCreate fires LanguageObserver, so every reseed also restores a missing
 * lang/{code}/ folder. No flag media is attached — flag_url falls back to the
 * bundled artwork keyed by Language::FLAG_ASSETS.
 */
class LanguagesSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['code' => 'en', 'name' => 'English', 'is_rtl' => false, 'is_enabled' => true, 'is_default' => true],
            ['code' => 'ar', 'name' => 'Arabic', 'is_rtl' => true, 'is_enabled' => true, 'is_default' => false],
            ['code' => 'fr', 'name' => 'French', 'is_rtl' => false, 'is_enabled' => false, 'is_default' => false],
            ['code' => 'es', 'name' => 'Spanish', 'is_rtl' => false, 'is_enabled' => false, 'is_default' => false],
            ['code' => 'de', 'name' => 'German', 'is_rtl' => false, 'is_enabled' => false, 'is_default' => false],
            ['code' => 'it', 'name' => 'Italian', 'is_rtl' => false, 'is_enabled' => false, 'is_default' => false],
            ['code' => 'pt', 'name' => 'Portuguese', 'is_rtl' => false, 'is_enabled' => false, 'is_default' => false],
            ['code' => 'ru', 'name' => 'Russian', 'is_rtl' => false, 'is_enabled' => false, 'is_default' => false],
            ['code' => 'hi', 'name' => 'Hindi', 'is_rtl' => false, 'is_enabled' => false, 'is_default' => false],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(['code' => $language['code']], Arr::except($language, 'code'));
        }
    }
}
