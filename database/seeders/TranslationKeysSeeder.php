<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\TranslationKey;
use App\Services\Translations\TranslationService;
use Illuminate\Database\Seeder;

/**
 * Mirrors config('translations.keys') into the translation_keys registry, then
 * syncs the registry to disk: the English source string goes to lang/en, and
 * every other seeded locale gets the key as an empty line to fill in from the
 * Translations page. Adding a new line is: add it to the config array, reseed.
 *
 * Only ever fills keys that are ABSENT, so reseeding never overwrites what an
 * admin has translated. Uses putMany() rather than put(): one locked write per
 * (locale, group) instead of one per key, and no activity row — a seed is not an
 * admin edit.
 *
 * Runs after LanguagesSeeder, which is what creates the locales written to here.
 */
class TranslationKeysSeeder extends Seeder
{
    public function run(): void
    {
        $translations = app(TranslationService::class);

        $codes = Language::query()->pluck('code');

        foreach (config('translations.keys', []) as $group => $lines) {
            foreach (array_keys($lines) as $key) {
                TranslationKey::updateOrCreate(['group' => $group, 'key' => $key]);
            }

            foreach ($codes as $code) {
                $translations->putMany(
                    $code,
                    $group,
                    $code === 'en' ? $lines : array_fill_keys(array_keys($lines), ''),
                );
            }
        }
    }
}
