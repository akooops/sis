<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\TranslationKey;
use App\Services\Translations\TranslationService;
use Illuminate\Database\Seeder;

/**
 * Mirrors the catalogue under config/translations/ into the translation_keys
 * registry, then writes it to disk — one lang/{code}/{group}.php per locale per
 * group. Adding a line is: add it to the group file, reseed.
 *
 * lang/ is GENERATED OUTPUT and gitignored, so this is not a convenience: it is
 * how a fresh clone gets any translations at all. The tracked source is
 * config/translations/{group}.php, whose values are locale => string maps.
 *
 * Only ever fills keys that are ABSENT, so reseeding never overwrites what an
 * admin has translated through the Translations page. Uses putMany() rather than
 * put(): one locked write per (locale, group) instead of one per key, and no
 * activity row — a seed is not an admin edit.
 *
 * Runs after LanguagesSeeder, which creates the locales written to here.
 */
class TranslationKeysSeeder extends Seeder
{
    public function run(): void
    {
        $translations = app(TranslationService::class);

        $codes = Language::query()->pluck('code');

        /*
         * The escape hatch, and it exists because lang/ is generated output.
         *
         * onlyMissing is what keeps a reseed safe for an admin's edits — but it
         * also means correcting a string in config never reaches an install that
         * already wrote that file. One env flag is cheaper than a bespoke
         * translations:sync command, and it leaves the safe behaviour as default.
         */
        $overwrite = (bool) env('TRANSLATIONS_OVERWRITE', false);

        foreach ($translations->groups() as $group) {
            foreach (array_keys($translations->catalogue($group)) as $key) {
                TranslationKey::updateOrCreate(['group' => $group, 'key' => $key]);
            }

            foreach ($codes as $code) {
                $translations->putMany(
                    $code,
                    $group,
                    $translations->linesFor($code, $group),
                    onlyMissing: ! $overwrite,
                );
            }
        }
    }
}
