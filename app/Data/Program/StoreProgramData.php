<?php

namespace App\Data\Program;

use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Create takes the default locale only; the rest come from the edit form. No `order`: a new program goes last. */
class StoreProgramData extends Data
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $title,
        public string $subtitle,
        public string $description,
        public string $content,
        public string $thumbnail,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('programs', 'slug')],

            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            // Uncapped: JSON column, real limit is max_allowed_packet.
            'content' => ['required', 'string'],

            'thumbnail' => ['required', 'string', new CleanUpload('images')],
        ];
    }
}
