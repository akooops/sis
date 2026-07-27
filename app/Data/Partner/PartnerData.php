<?php

namespace App\Data\Partner;

use App\Models\Partner;
use Spatie\LaravelData\Data;

/** Output DTO for a partner. `order` is read-only — it comes from the reorder endpoint. */
class PartnerData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $url,
        public int $order,
        public ?string $logo_url,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Partner $partner): self
    {
        return new self(
            id: $partner->id,
            name: $partner->name,
            url: $partner->url,
            order: $partner->order,
            logo_url: $partner->logo_url,
            created_at: $partner->created_at?->toIso8601String(),
            updated_at: $partner->updated_at?->toIso8601String(),
        );
    }
}
