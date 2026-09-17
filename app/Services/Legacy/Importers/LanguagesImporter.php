<?php

namespace App\Services\Legacy\Importers;

use App\Models\Language;
use App\Services\Legacy\LegacyImporter;

/**
 * The locales the old site actually ran in, ENABLED here.
 *
 * RUNS FIRST, AND IT MATTERS MORE THAN IT LOOKS. This app seeds nine languages
 * but enables only some of them, and HasEnabledTranslations narrows every read to
 * the enabled set — so an Arabic `title` imported against a locale nobody has
 * enabled is stored correctly, renders nowhere, and looks like the translations
 * were lost.
 *
 * The old table had no `is_enabled` column because it had no notion of a locale
 * that exists but is off: every row it held was a language the site served. So a
 * legacy language turns its counterpart ON here, matched by code, and one this
 * app has never heard of is CREATED rather than dropped — LanguageObserver then
 * makes its `lang/` folder, and the catalogue can be seeded into it afterwards.
 *
 * `is_default` is carried, and exactly one row can hold it: the observer enforces
 * that with a builder update in saved(), so writing a second default demotes the
 * first rather than leaving two.
 */
class LanguagesImporter extends LegacyImporter
{
    public function module(): string
    {
        return 'languages';
    }

    public function describe(): string
    {
        return 'Languages (enables the locales the old site served)';
    }

    public function sources(): array
    {
        return ['languages'];
    }

    public function run(): void
    {
        $map = config('legacy.locales', []);

        $this->each('languages', function (object $row) use ($map) {
            $code = $map[$row->code] ?? $row->code;

            $model = $this->model(Language::class, 'languages', (int) $row->id, ['code' => $code]);

            if (! $model->exists) {
                $model->code = $code;
                $model->name = (string) ($row->name ?: $code);
                $model->is_rtl = (bool) ($row->is_rtl ?? false);

                $this->c->note(
                    "Locale [{$code}] was not seeded by this app and has been created. "
                    .'Seed the translation catalogue into it, or its UI strings fall back to English.'
                );
            }

            // The whole point: a language the old site served is one this one
            // serves. Never turned OFF from here — that is an admin's decision.
            $model->is_enabled = true;

            if ((bool) ($row->is_default ?? false)) {
                $model->is_default = true;
            }

            $this->save($model, 'languages', (int) $row->id);
        });
    }
}
