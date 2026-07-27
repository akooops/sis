<?php

namespace App\Data\Grade;

use App\Models\Grade;
use App\Models\Language;
use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Every locale at once. Errors come back keyed `title.ar`. */
class UpdateGradeData extends Data
{
    public function __construct(
        public string $program_id,
        public string $name,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<int, string> */
        public array $guidelines = [],
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $codes = Language::enabledCodes();
        $default = Language::defaultCode();

        $rules = [
            'program_id' => ['required', 'string', Rule::exists('programs', 'id')],

            'name' => ['required', 'string', 'max:255'],
            // array:en,ar also rejects unknown keys.
            'title' => ['required', 'array:'.implode(',', $codes)],

            // Exactly the list, in order — an id dropped here is detached.
            'guidelines' => ['sometimes', 'array'],
            'guidelines.*' => ['string', new CleanUpload(Grade::GUIDELINE_TYPES)],
        ];

        foreach ($codes as $code) {
            $rules["title.{$code}"] = $code === $default
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
