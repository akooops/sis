<?php

namespace App\Data\Calendar;

use App\Rules\CleanUpload;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Create takes the default locale only; the rest come from the edit form. */
class StoreCalendarData extends Data
{
    public function __construct(
        public string $name,
        public string $title,
        public string $start_date,
        public string $end_date,
        public string $file,
        public bool $is_active = true,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],

            'start_date' => ['required', 'date'],
            // A period cannot end before it starts; a one-day calendar is fine.
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],

            'is_active' => ['sometimes', 'boolean'],

            // A calendar is a PDF or an image of one.
            'file' => ['required', 'string', new CleanUpload(['documents', 'images'])],
        ];
    }
}
