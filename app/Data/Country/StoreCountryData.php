<?php

namespace App\Data\Country;

use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Create takes the default locale only; the rest come from the edit form. */
class StoreCountryData extends Data
{
    public function __construct(
        public string $name,
        public string $code,
        public ?string $alpha3,
        public ?string $flag,
        public string $title,
        public string $nationality,
        public bool $is_enabled = true,
        public string|Optional|null $flag_image = null,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'size:2', 'alpha', 'uppercase', Rule::unique('countries', 'code')],
            'alpha3' => ['nullable', 'string', 'size:3', 'alpha', 'uppercase', Rule::unique('countries', 'alpha3')],

            // A filename under public/assets/admin/media/flags; blank falls back to the UN flag.
            'flag' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],

            'title' => ['required', 'string', 'max:255'],
            'nationality' => ['required', 'string', 'max:255'],

            'is_enabled' => ['sometimes', 'boolean'],

            // An uploaded override, which wins over the bundled slug.
            'flag_image' => ['sometimes', 'nullable', 'string', new CleanUpload('images')],
        ];
    }
}
