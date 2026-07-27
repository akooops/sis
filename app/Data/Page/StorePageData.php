<?php

namespace App\Data\Page;

use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Create takes the default locale only; the rest come from the edit form. */
class StorePageData extends Data
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $title,
        public ?string $description,
        public ?string $content,
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
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('pages', 'slug')],

            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'content' => ['required', 'string'],

            'status' => ['required', Rule::in(['draft', 'scheduled', 'published'])],
            'published_at' => $status === 'scheduled'
                ? ['required', 'date', 'after:now']
                : ['nullable', 'date'],

            'css_url' => ['nullable', 'url', 'max:2048'],
            'custom_css' => ['nullable', 'string', 'max:65535'],

            'thumbnail' => ['required', 'string', new CleanUpload('images')],
        ];
    }
}
