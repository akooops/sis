<?php

namespace App\Data\BrandAssetGroup;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Create takes the default locale only; the rest come from the edit form.
 * No `order`: a new group goes last, and reordering is its own endpoint.
 */
class StoreBrandAssetGroupData extends Data
{
    public function __construct(
        public string $brand_id,
        public string $name,
        public string $title,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'brand_id' => ['required', 'string', Rule::exists('brands', 'id')],

            // Unique within the brand only: two brands may both have "Fonts".
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('brand_asset_groups', 'name')->where('brand_id', $context->payload['brand_id'] ?? null),
            ],

            'title' => ['required', 'string', 'max:255'],
        ];
    }
}
