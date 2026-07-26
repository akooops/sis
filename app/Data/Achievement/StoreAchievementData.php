<?php

namespace App\Data\Achievement;

use App\Enums\CategoryType;
use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class StoreAchievementData extends Data
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $category_id,
        public string $title,
        public string $description,
        public string $content,
        public ?string $done_by,
        public string $status,
        public ?string $published_at,
        public string $achieved_at,
        public ?string $css_url,
        public ?string $custom_css,
        public string $thumbnail,
        /** @var array<int, string> */
        public array $images = [],
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $status = $context->payload['status'] ?? 'draft';

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('achievements', 'slug')],

            // Scoped to its own type, so an articles category can't be filed here.
            'category_id' => [
                'nullable', 'string',
                Rule::exists('categories', 'id')->where('type', CategoryType::Achievements->value),
            ],

            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'content' => ['required', 'string'],
            'done_by' => ['nullable', 'string', 'max:255'],

            'status' => ['required', Rule::in(['draft', 'scheduled', 'published'])],
            'published_at' => $status === 'scheduled'
                ? ['required', 'date', 'after:now']
                : ['nullable', 'date'],

            // When it happened. Deliberately not constrained to the past — an
            // upcoming award can be prepared ahead of the ceremony.
            'achieved_at' => ['required', 'date'],

            'css_url' => ['nullable', 'url', 'max:2048'],
            'custom_css' => ['nullable', 'string', 'max:65535'],

            'thumbnail' => ['required', 'string', new CleanUpload('images')],

            'images' => ['sometimes', 'array'],
            'images.*' => ['string', new CleanUpload('images')],
        ];
    }
}
