<?php

namespace App\Data\Translation;

use App\Models\TranslationKey;
use Spatie\LaravelData\Data;

/**
 * Output DTO for a registry key — the key itself, with no locale attached.
 * A key plus one locale's value is TranslationLineData.
 */
class TranslationKeyData extends Data
{
    public function __construct(
        public string $id,
        public string $group,
        public string $key,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(TranslationKey $translationKey): self
    {
        return new self(
            id: $translationKey->id,
            group: $translationKey->group,
            key: $translationKey->key,
            created_at: $translationKey->created_at?->toIso8601String(),
            updated_at: $translationKey->updated_at?->toIso8601String(),
        );
    }
}
