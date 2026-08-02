<?php

namespace App\Data\BrandAssetGroup;

use App\Models\Language;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Every locale at once. Errors come back keyed `title.ar`. */
class UpdateBrandAssetGroupData extends Data
{
    public function __construct(
        public string $brand_id,
        public string $name,
        /** @var array<string, string|null> */
        public array $title,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $codes = Language::enabledCodes();
        $default = Language::defaultCode();

        $rules = [
            'brand_id' => ['required', 'string', Rule::exists('brands', 'id')],

            // Unique within the brand only: two brands may both have "Fonts".
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('brand_asset_groups', 'name')
                    ->where('brand_id', $context->payload['brand_id'] ?? null)
                    ->ignore(request()->route('brandAssetGroup')),
            ],

            // array:en,ar also rejects unknown keys.
            'title' => ['required', 'array:'.implode(',', $codes)],
        ];

        foreach ($codes as $code) {
            $rules["title.{$code}"] = $code === $default
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
