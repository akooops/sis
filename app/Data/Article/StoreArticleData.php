<?php

namespace App\Data\Article;

use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Creating an article asks for the DEFAULT language only — title/description/
 * content are plain strings here, and the other locales are filled in afterwards
 * through the edit form's Translations tab.
 */
class StoreArticleData extends Data
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $title,
        public string $description,
        public string $content,
        public string $status,
        public ?string $published_at,
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
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('articles', 'slug')],

            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            // Uncapped: a JSON column, whose real ceiling is max_allowed_packet.
            'content' => ['required', 'string'],

            // Hidden is excluded at birth — something never public cannot be withdrawn.
            'status' => ['required', Rule::in(['draft', 'scheduled', 'published'])],
            // Only a schedule asks for a date, and it must be in the future.
            // Publishing is always "now": the controller stamps it.
            'published_at' => $status === 'scheduled'
                ? ['required', 'date', 'after:now']
                : ['nullable', 'date'],

            'css_url' => ['nullable', 'url', 'max:2048'],
            'custom_css' => ['nullable', 'string', 'max:65535'],

            'thumbnail' => ['required', 'string', new CleanUpload('images')],

            'images' => ['sometimes', 'array'],
            'images.*' => ['string', new CleanUpload('images')],
        ];
    }
}
