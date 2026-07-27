<?php

namespace App\Data\Category;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Create takes the default locale only; the rest come from the edit form. */
class StoreCategoryData extends Data
{
    public function __construct(
        public string $name,
        public string $code,
        public string $title,
        public string $color,
        public bool $is_default = false,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'code' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('categories', 'code')],
            'is_default' => ['boolean'],
        ];
    }
}
