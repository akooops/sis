<?php

namespace App\Data\Category;

use App\Enums\CategoryType;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class StoreCategoryData extends Data
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
            // Unique within the type only — see the migration's composite index.
            'code' => [
                'required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories', 'code')->where('type', $context->payload['type'] ?? null),
            ],
            'type' => ['required', Rule::in(CategoryType::values())],
        ];
    }
}
