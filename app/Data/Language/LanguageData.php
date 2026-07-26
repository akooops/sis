<?php

namespace App\Data\Language;

use App\Models\Language;
use Spatie\LaravelData\Data;

/**
 * Output DTO for a language. `flag_url` is always present — it falls back to the
 * bundled flag artwork for the code when no image has been uploaded.
 */
class LanguageData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $code,
        public bool $is_default,
        public bool $is_rtl,
        public bool $is_enabled,
        public ?string $flag_url,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Language $language): self
    {
        return new self(
            id: $language->id,
            name: $language->name,
            code: $language->code,
            is_default: $language->is_default,
            is_rtl: $language->is_rtl,
            is_enabled: $language->is_enabled,
            flag_url: $language->flag_url,
            created_at: $language->created_at?->toIso8601String(),
            updated_at: $language->updated_at?->toIso8601String(),
        );
    }
}
