<?php

namespace App\Data\Translation;

use App\Models\TranslationKey;
use Spatie\LaravelData\Data;

/**
 * One registry key as seen through one locale: the key comes from the database,
 * the value from lang/{locale}/{group}.php. `id` is the translation_keys id —
 * it is what the table rows on and what the update route binds.
 *
 * Reads the transient attributes TranslationService::hydrate() hangs on the
 * model, so a paginated key page can be collected straight into this.
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
