<?php

namespace App\Data\Brand;

use App\Models\Brand;
use Spatie\LaravelData\Data;

/** Output DTO for a brand. `asset_groups_count` comes from withCount — the groups are their own endpoint. */
class BrandData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $description,
        /** @var array<string, string|null> */
        public array $content,
        public string $status,
        public ?string $published_at,
        public ?string $css_url,
        public ?string $custom_css,
        public ?string $thumbnail_url,
        public int $asset_groups_count,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Brand $brand): self
    {
        return new self(
            id: $brand->id,
            name: $brand->name,
            slug: $brand->slug,
            title: $brand->enabledTranslations('title'),
            description: $brand->enabledTranslations('description'),
            content: $brand->enabledTranslations('content'),
            status: $brand->status->getValue(),
            published_at: $brand->published_at?->toIso8601String(),
            css_url: $brand->css_url,
            custom_css: $brand->custom_css,
            thumbnail_url: $brand->thumbnail_url,
            asset_groups_count: $brand->asset_groups_count ?? 0,
            created_at: $brand->created_at?->toIso8601String(),
            updated_at: $brand->updated_at?->toIso8601String(),
        );
    }
}
