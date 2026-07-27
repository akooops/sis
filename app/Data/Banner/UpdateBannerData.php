<?php

namespace App\Data\Banner;

use App\Models\Banner;
use App\Models\Language;
use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Every locale at once. Errors come back keyed `title.ar`.
 * No `order`: reordering is its own endpoint.
 */
class UpdateBannerData extends Data
{
    public function __construct(
        public string $name,
        public ?string $url,
        public ?string $linkable_type,
        public ?string $linkable_id,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $cta,
        public string|Optional|null $thumbnail = null,
        public string|Optional|null $video = null,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $codes = Language::enabledCodes();
        $default = Language::defaultCode();

        $rules = [
            'name' => ['required', 'string', 'max:255'],

            // Both link kinds are optional, but never both at once. Sending null
            // for all three clears the link.
            'url' => ['nullable', 'url', 'max:2048', 'prohibits:linkable_type,linkable_id'],
            'linkable_type' => ['nullable', 'required_with:linkable_id', 'string', Rule::in(Banner::LINKABLE_TYPES)],
            'linkable_id' => ['nullable', 'required_with:linkable_type', 'string'],

            // array:en,ar also rejects unknown keys.
            'title' => ['required', 'array:'.implode(',', $codes)],
            'cta' => ['required', 'array:'.implode(',', $codes)],

            'thumbnail' => ['sometimes', 'nullable', 'string', new CleanUpload('images')],
            'video' => ['sometimes', 'nullable', 'string', new CleanUpload('videos')],
        ];

        // The table follows the alias; an unknown one already fails on linkable_type.
        if ($table = Banner::linkableTable($context->payload['linkable_type'] ?? null)) {
            $rules['linkable_id'][] = Rule::exists($table, 'id');
        }

        // Per locale, not `title.*` plus an override: merged rule sets read badly.
        foreach ($codes as $code) {
            $isDefault = $code === $default;

            $rules["title.{$code}"] = $isDefault
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];
            $rules["cta.{$code}"] = $isDefault
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
