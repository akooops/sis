<?php

namespace App\Data\Stream;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Create takes the default locale only; the rest come from the edit form.
 * No `order`: a new stream goes last, and reordering is its own endpoint.
 */
class StoreStreamData extends Data
{
    public function __construct(
        public string $program_id,
        public string $name,
        public string $slug,
        public string $color,
        public string $title,
        public string $description,
        public string $content,
        public string $cta,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'program_id' => ['required', 'string', Rule::exists('programs', 'id')],

            'name' => ['required', 'string', 'max:255'],
            // Unique within the program only: two programs may both have "british".
            'slug' => [
                'required', 'string', 'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('streams', 'slug')->where('program_id', $context->payload['program_id'] ?? null),
            ],
            // Goes straight into a style attribute, hence the strict shape.
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            // Uncapped: JSON column, real limit is max_allowed_packet.
            'content' => ['required', 'string'],
            'cta' => ['required', 'string', 'max:255'],
        ];
    }
}
