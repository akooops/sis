<?php

namespace App\Data\Calendar;

use App\Models\Language;
use App\Rules\CleanUpload;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Every locale at once. Errors come back keyed `title.ar`. */
class UpdateCalendarData extends Data
{
    public function __construct(
        public string $name,
        /** @var array<string, string|null> */
        public array $title,
        public string $start_date,
        public string $end_date,
        public bool $is_active = true,
        public string|Optional|null $file = null,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $codes = Language::enabledCodes();
        $default = Language::defaultCode();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            // array:en,ar also rejects unknown keys.
            'title' => ['required', 'array:'.implode(',', $codes)],

            'start_date' => ['required', 'date'],
            // A period cannot end before it starts; a one-day calendar is fine.
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],

            'is_active' => ['sometimes', 'boolean'],

            'file' => ['sometimes', 'nullable', 'string', new CleanUpload(['documents', 'images'])],
        ];

        // Per locale, not `title.*` plus an override: merged rule sets read badly.
        foreach ($codes as $code) {
            $rules["title.{$code}"] = $code === $default
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
