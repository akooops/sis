<?php

namespace App\Data\Article;

use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Create takes the default locale only; the rest come from the edit form. */
class StoreArticleData extends Data
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $category_id,
        public string $title,
        public string $description,
        public string $content,
        public string $status,
        public ?string $published_at,
        public ?string $css_url,
        public ?string $custom_css,
        public string $thumbnail,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $status = $context->payload['status'] ?? 'draft';

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('articles', 'slug')],

            // Blank resolves to the default category in the controller.
            'category_id' => ['nullable', 'string', Rule::exists('categories', 'id')],

            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            // Uncapped: JSON column, real limit is max_allowed_packet.
            'content' => ['required', 'string'],

            // No 'hidden' at birth: nothing public to withdraw yet.
            'status' => ['required', Rule::in(['draft', 'scheduled', 'published'])],
            // Only a schedule needs a date; publishing is stamped by the controller.
            'published_at' => $status === 'scheduled'
                ? ['required', 'date', 'after:now']
                : ['nullable', 'date'],

            'css_url' => ['nullable', 'url', 'max:2048'],
            'custom_css' => ['nullable', 'string', 'max:65535'],

            'thumbnail' => ['required', 'string', new CleanUpload('images')],
        ];
    }
}
