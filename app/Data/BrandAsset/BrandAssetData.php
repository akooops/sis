<?php

namespace App\Data\BrandAsset;

use App\Data\BrandAssetGroup\BrandAssetGroupData;
use App\Models\BrandAsset;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/** `order` is read-only here — it is written by the reorder endpoint. */
class BrandAssetData extends Data
{
    public function __construct(
        public string $id,
        public string $brand_asset_group_id,
        public string $name,
        public int $order,
        /** @var array<string, string|null> */
        public array $title,
        public ?string $file_url,
        public ?string $file_name,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|BrandAssetGroupData|null $group,
    ) {}

    public static function fromModel(BrandAsset $asset): self
    {
        $file = $asset->getMedia(BrandAsset::FILE_COLLECTION)->first();

        return new self(
            id: $asset->id,
            brand_asset_group_id: $asset->brand_asset_group_id,
            name: $asset->name,
            order: $asset->order,
            title: $asset->enabledTranslations('title'),
            file_url: $asset->file_url,
            // The real filename, for the download label — file_name on media is a ULID.
            file_name: $file?->name,
            created_at: $asset->created_at?->toIso8601String(),
            updated_at: $asset->updated_at?->toIso8601String(),
            group: Lazy::whenLoaded('group', $asset, fn () => $asset->group ? BrandAssetGroupData::from($asset->group) : null),
        );
    }
}
