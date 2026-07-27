<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

/**
 * Mirrors config('countries.list') into the countries table. Adding a country or
 * fixing a demonym is: edit the config array, then reseed.
 *
 * Keyed on `code` and refreshed on every run, like LanguagesSeeder and unlike the
 * firstOrCreate content seeders: this is ISO metadata, not something an admin
 * authors, so a reseed SHOULD correct it. `is_enabled` is left out of the payload
 * — that one IS an admin decision; the column defaults to true on create.
 */
class CountriesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('countries.list', []) as $country) {
            $model = Country::firstOrNew(['code' => $country['code']]);

            // Merge: a locale the config does not carry (fr, es…) survives a reseed.
            $model->fill($model->mergeTranslations(Arr::except($country, 'code')))->save();
        }
    }
}
