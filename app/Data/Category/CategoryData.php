<?php

namespace App\Data\Category;

use App\Models\Category;
use Spatie\LaravelData\Data;

class CategoryData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $code,
        public string $type,
        public string $type_label,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Category $category): self
    {
        return new self(
            id: $category->id,
            name: $category->name,
            code: $category->code,
            type: $category->type->value,
            type_label: $category->type->label(),
            created_at: $category->created_at?->toIso8601String(),
            updated_at: $category->updated_at?->toIso8601String(),
        );
    }
}
