<?php

namespace App\Data\Banner;

use App\Models\Banner;
use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Create takes the default locale only; the rest come from the edit form.
 * No `order`: a new banner goes last, and reordering is its own endpoint.
 */
class StoreBannerData extends Data
{
    public function __construct(
        public string $name,
        public ?string $url,
        public ?string $linkable_type,
        public ?string $linkable_id,
        public string $title,
        public string $cta,
        public string $thumbnail,
        public ?string $video,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],

            // Both link kinds are optional, but never both at once.
            'url' => ['nullable', 'url', 'max:2048', 'prohibits:linkable_type,linkable_id'],
            'linkable_type' => ['nullable', 'required_with:linkable_id', 'string', Rule::in(Banner::LINKABLE_TYPES)],
            'linkable_id' => ['nullable', 'required_with:linkable_type', 'string'],

            'title' => ['required', 'string', 'max:255'],
            'cta' => ['required', 'string', 'max:255'],

            'thumbnail' => ['required', 'string', new CleanUpload('images')],
            'video' => ['nullable', 'string', new CleanUpload('videos')],
        ];

        // The table follows the alias; an unknown one already fails on linkable_type.
        if ($table = Banner::linkableTable($context->payload['linkable_type'] ?? null)) {
            $rules['linkable_id'][] = Rule::exists($table, 'id');
        }

        return $rules;
    }
}
