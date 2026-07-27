<?php

namespace App\Data\Program;

use App\Models\Language;
use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Every locale at once. Errors come back keyed `title.ar`. */
class UpdateProgramData extends Data
{
    public function __construct(
        public string $name,
        public string $slug,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $description,
        /** @var array<string, string|null> */
        public array $subtitle,
        /** @var array<string, string|null> */
        public array $content,
        public string|Optional|null $thumbnail = null,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $codes = Language::enabledCodes();
        $default = Language::defaultCode();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('programs', 'slug')->ignore(request()->route('program')),
            ],

            // array:en,ar also rejects unknown keys.
            'title' => ['required', 'array:'.implode(',', $codes)],
            'subtitle' => ['required', 'array:'.implode(',', $codes)],
            'description' => ['required', 'array:'.implode(',', $codes)],
            'content' => ['required', 'array:'.implode(',', $codes)],

            'thumbnail' => ['sometimes', 'nullable', 'string', new CleanUpload('images')],
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
            $rules["subtitle.{$code}"] = $isDefault
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
