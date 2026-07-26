<?php

namespace App\Data\Category;

use App\Enums\CategoryType;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class UpdateCategoryData extends Data
{
    public function __construct(
        public string $name,
        public string $code,
        public string $type,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories', 'code')
                    ->where('type', $context->payload['type'] ?? null)
                    ->ignore(request()->route('category')),
            ],
            // Changing the type moves the category to a different list, which
            // leaves anything already filed under it pointing at a category its
            // own picker no longer offers. The controller refuses that while the
            // category is in use.
            'type' => ['required', Rule::in(CategoryType::values())],
        ];
    }
}
