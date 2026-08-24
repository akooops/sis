<?php

namespace App\Data\Facility;

use App\Models\Language;
use App\Rules\CleanUpload;
use App\Traits\Css\SanitisesCustomCss;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Every locale at once. Errors come back keyed `title.ar`. */
class UpdateFacilityData extends Data
{
    use SanitisesCustomCss;

    public function __construct(
        public string $name,
        public string $slug,
        public int $order,
        public string $status,
        public ?string $published_at,
        public ?string $css_url,
        public ?string $custom_css,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $description,
        /** @var array<string, string|null> */
        public array $content,
        public string|Optional|null $thumbnail = null,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $codes = Language::enabledCodes();
        $default = Language::defaultCode();
        $status = $context->payload['status'] ?? 'draft';

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('facilities', 'slug')->ignore(request()->route('facility')),
            ],

            'order' => ['required', 'integer', 'min:0', 'max:65535'],

            'status' => ['required', Rule::in(['draft', 'scheduled', 'published', 'hidden'])],
            'published_at' => $status === 'scheduled'
                ? ['required', 'date', 'after:now']
                : ['nullable', 'date'],

            'css_url' => ['nullable', 'url:http,https', 'max:2048'],
            'custom_css' => ['nullable', 'string', 'max:65535'],

            // array:en,ar also rejects unknown keys.
            'title' => ['required', 'array:'.implode(',', $codes)],
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
        }

        return $rules;
    }
}
