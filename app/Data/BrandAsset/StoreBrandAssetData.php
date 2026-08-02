<?php

namespace App\Data\BrandAsset;

use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Create takes the default locale only; the rest come from the edit form.
 * No `order`: a new asset goes last, and reordering is its own endpoint.
 */
class StoreBrandAssetData extends Data
{
    public function __construct(
        public string $brand_asset_group_id,
        public string $name,
        public string $title,
        public string $file,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'brand_asset_group_id' => ['required', 'string', Rule::exists('brand_asset_groups', 'id')],

            'name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            // Any uploadable type: a brand asset may be a logo, a font or a video sting.
            'file' => ['required', 'string', new CleanUpload(['documents', 'images', 'videos', 'audio'])],
        ];
    }
}
