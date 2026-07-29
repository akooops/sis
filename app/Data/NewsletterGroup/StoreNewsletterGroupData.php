<?php

namespace App\Data\NewsletterGroup;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Create takes the default locale only; the rest come from the edit form. */
class StoreNewsletterGroupData extends Data
{
    public function __construct(
        public string $name,
        public string $code,
        public string $title,
        public string $description,
        public bool $is_default = false,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('newsletter_groups', 'code')],

            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],

            'is_default' => ['boolean'],
        ];
    }
}
