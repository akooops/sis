<?php

namespace App\Data\Translation;

use App\Models\TranslationKey;
use Spatie\LaravelData\Data;

/**
 * One key through one locale: the key from the DB, the value from
 * lang/{locale}/{group}.php. `id` is the translation_keys id, which the table
 * rows on and the update route binds.
 *
 * Reads the transient attributes TranslationService::hydrate() sets.
 */
class TranslationLineData extends Data
{
    public function __construct(
        public string $id,
        public string $group,
        public string $key,
        public ?string $locale,
        public ?string $value,
        public bool $is_translated,
    ) {}

    public static function fromModel(TranslationKey $translationKey): self
    {
        $value = $translationKey->getAttribute('value');

        return new self(
            id: $translationKey->id,
            group: $translationKey->group,
            key: $translationKey->key,
            locale: $translationKey->getAttribute('locale'),
            value: is_string($value) ? $value : null,
            is_translated: (bool) $translationKey->getAttribute('is_translated'),
        );
    }
}
