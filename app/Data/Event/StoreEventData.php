<?php

namespace App\Data\Event;

use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class StoreEventData extends Data
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $title,
        public string $description,
        public string $content,
        public string $status,
        public ?string $published_at,
        public string $start_at,
        public string $end_at,
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
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('events', 'slug')],

            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'content' => ['required', 'string'],

            'status' => ['required', Rule::in(['draft', 'scheduled', 'published'])],
            'published_at' => $status === 'scheduled'
                ? ['required', 'date', 'after:now']
                : ['nullable', 'date'],

            // When the event runs — deliberately NOT constrained to the future.
            // Back-filling a past event is a normal thing to want; only the
            // ordering of the two matters.
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after_or_equal:start_at'],

            'css_url' => ['nullable', 'url', 'max:2048'],
            'custom_css' => ['nullable', 'string', 'max:65535'],

            'thumbnail' => ['required', 'string', new CleanUpload('images')],

            'images' => ['sometimes', 'array'],
            'images.*' => ['string', new CleanUpload('images')],
        ];
    }
}
