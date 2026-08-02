<?php

namespace App\Data\ContactDetail;

use App\Models\ContactDetail;
use Spatie\LaravelData\Data;

/**
 * The type's name and icon are deliberately absent: the UI reads the registry
 * once from the contact-types endpoint, so the catalogue stays single-sourced
 * instead of being copied onto every row.
 */
class ContactDetailData extends Data
{
    public function __construct(
        public string $id,
        public string $type,
        public string $name,
        /** @var array<string, string|null> */
        public array $title,
        public ?string $value,
        /** @var array<string, string|null> */
        public array $address,
        public ?string $platform,
        public ?string $map_url,
        public ?float $latitude,
        public ?float $longitude,
        public int $order,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(ContactDetail $detail): self
    {
        return new self(
            id: $detail->id,
            type: $detail->type,
            name: $detail->name,
            title: $detail->enabledTranslations('title'),
            value: $detail->value,
            address: $detail->enabledTranslations('address'),
            platform: $detail->platform,
            map_url: $detail->map_url,
            latitude: $detail->latitude,
            longitude: $detail->longitude,
            order: $detail->order,
            created_at: $detail->created_at?->toIso8601String(),
            updated_at: $detail->updated_at?->toIso8601String(),
        );
    }
}
