<?php

namespace App\Data\BrandAssetGroup;

use App\Data\Brand\BrandData;
use App\Models\BrandAssetGroup;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * `order` is read-only here — it is written by the reorder endpoint — and
 * `assets_count` comes from withCount, the assets themselves being their own endpoint.
 */
class BrandAssetGroupData extends Data
{
    public function __construct(
        public string $id,
        public string $brand_id,
        public string $name,
        public int $order,
        /** @var array<string, string|null> */
        public array $title,
        public int $assets_count,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|BrandData|null $brand,
    ) {}

    public static function fromModel(BrandAssetGroup $group): self
    {
        return new self(
            id: $group->id,
            brand_id: $group->brand_id,
            name: $group->name,
            order: $group->order,
            title: $group->enabledTranslations('title'),
            assets_count: $group->assets_count ?? 0,
            created_at: $group->created_at?->toIso8601String(),
            updated_at: $group->updated_at?->toIso8601String(),
            brand: Lazy::whenLoaded('brand', $group, fn () => $group->brand ? BrandData::from($group->brand) : null),
        );
    }
}
