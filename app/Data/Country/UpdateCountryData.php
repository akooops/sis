<?php

namespace App\Data\Country;

use App\Models\Language;
use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Every locale at once. Errors come back keyed `title.ar`. */
class UpdateCountryData extends Data
{
    public function __construct(
        public string $name,
        public string $code,
        public ?string $alpha3,
        public ?string $flag,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $nationality,
        public bool $is_enabled = true,
        public string|Optional|null $flag_image = null,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $codes = Language::enabledCodes();
        $default = Language::defaultCode();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'size:2', 'alpha', 'uppercase',
                Rule::unique('countries', 'code')->ignore(request()->route('country')),
            ],
            'alpha3' => [
                'nullable', 'string', 'size:3', 'alpha', 'uppercase',
                Rule::unique('countries', 'alpha3')->ignore(request()->route('country')),
            ],

            // A filename under public/assets/admin/media/flags; blank falls back to the UN flag.
            'flag' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],

            // array:en,ar also rejects unknown keys.
            'title' => ['required', 'array:'.implode(',', $codes)],
            'nationality' => ['required', 'array:'.implode(',', $codes)],

            'is_enabled' => ['sometimes', 'boolean'],

            // An uploaded override, which wins over the bundled slug.
            'flag_image' => ['sometimes', 'nullable', 'string', new CleanUpload('images')],
        ];

        // Per locale, not `title.*` plus an override: merged rule sets read badly.
        foreach ($codes as $code) {
            $isDefault = $code === $default;

            $rules["title.{$code}"] = $isDefault
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];
            $rules["nationality.{$code}"] = $isDefault
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
