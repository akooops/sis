<?php

namespace App\Data\Category;

use App\Models\Language;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Every locale at once. Errors come back keyed `title.ar`. */
class UpdateCategoryData extends Data
{
    public function __construct(
        public string $name,
        public string $code,
        /** @var array<string, string|null> */
        public array $title,
        public string $color,
        public bool $is_default = false,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $codes = Language::enabledCodes();
        $default = Language::defaultCode();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            // array:en,ar also rejects unknown keys.
            'title' => ['required', 'array:'.implode(',', $codes)],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'code' => [
                'required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories', 'code')->ignore(request()->route('category')),
            ],

            'is_default' => ['boolean'],
        ];

        // Per locale, not `title.*` plus an override: merged rule sets read badly.
        foreach ($codes as $code) {
            $rules["title.{$code}"] = $code === $default
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
