<?php

namespace App\Data\Country;

use App\Models\Country;
use Spatie\LaravelData\Data;

/** `flag` is the bundled artwork slug; flag_url resolves it, or an upload. */
class CountryData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $code,
        public ?string $alpha3,
        public ?string $flag,
        public string $flag_url,
        public bool $is_enabled,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $nationality,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Country $country): self
    {
        return new self(
            id: $country->id,
            name: $country->name,
            code: $country->code,
            alpha3: $country->alpha3,
            flag: $country->flag,
            flag_url: $country->flag_url,
            is_enabled: $country->is_enabled,
            title: $country->enabledTranslations('title'),
            nationality: $country->enabledTranslations('nationality'),
            created_at: $country->created_at?->toIso8601String(),
            updated_at: $country->updated_at?->toIso8601String(),
        );
    }
}
