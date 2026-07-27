<?php

namespace App\Traits\Translations;

use App\Models\Language;
use Illuminate\Support\Arr;

/**
 * Read and write translations through the enabled locales only.
 *
 * A row can hold locales nobody has enabled — the seeders ship nine — while the
 * form only ever renders the enabled ones. So DTOs expose enabledTranslations(),
 * and updates go through mergeTranslations(), or saving a form would delete
 * every copy the form did not show.
 */
trait HasEnabledTranslations
{
    /** @return array<string, string|null> */
    public function enabledTranslations(string $key): array
    {
        return Arr::only($this->getTranslations($key), Language::enabledCodes());
    }

    /**
     * Overlay submitted translations onto the stored ones.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function mergeTranslations(array $attributes): array
    {
        foreach ($this->getTranslatableAttributes() as $key) {
            if (is_array($attributes[$key] ?? null)) {
                $attributes[$key] = array_merge($this->getTranslations($key), $attributes[$key]);
            }
        }

        return $attributes;
    }
}
