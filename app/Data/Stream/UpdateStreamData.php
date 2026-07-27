<?php

namespace App\Data\Stream;

use App\Models\Language;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Every locale at once. Errors come back keyed `title.ar`. */
class UpdateStreamData extends Data
{
    public function __construct(
        public string $program_id,
        public string $name,
        public string $slug,
        public string $color,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $description,
        /** @var array<string, string|null> */
        public array $content,
        /** @var array<string, string|null> */
        public array $cta,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $codes = Language::enabledCodes();
        $default = Language::defaultCode();

        $rules = [
            'program_id' => ['required', 'string', Rule::exists('programs', 'id')],

            'name' => ['required', 'string', 'max:255'],
            // Unique within the program only: two programs may both have "british".
            'slug' => [
                'required', 'string', 'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('streams', 'slug')
                    ->where('program_id', $context->payload['program_id'] ?? null)
                    ->ignore(request()->route('stream')),
            ],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            // array:en,ar also rejects unknown keys.
            'title' => ['required', 'array:'.implode(',', $codes)],
            'description' => ['required', 'array:'.implode(',', $codes)],
            'content' => ['required', 'array:'.implode(',', $codes)],
            'cta' => ['required', 'array:'.implode(',', $codes)],
        ];

        // Per locale, not `title.*` plus an override: merged rule sets read badly.
        foreach ($codes as $code) {
            $isDefault = $code === $default;

            $rules["title.{$code}"] = $isDefault
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];
            $rules["description.{$code}"] = $isDefault
                ? ['required', 'string', 'max:1000']
                : ['nullable', 'string', 'max:1000'];
            $rules["content.{$code}"] = $isDefault
                ? ['required', 'string']
                : ['nullable', 'string'];
            $rules["cta.{$code}"] = $isDefault
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
