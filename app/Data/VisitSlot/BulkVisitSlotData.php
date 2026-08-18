<?php

namespace App\Data\VisitSlot;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Generate a term of slots in one go.
 *
 * A DAY-OF-WEEK MASK OVER A DATE RANGE, then a repeating window inside each day.
 * "Sundays and Tuesdays through December, 09:00 to 12:00, 60-minute tours, 15
 * minutes between" is three tours a day on two days a week — which is what a
 * school actually runs, and what the old generator could not express: it wrote ONE
 * slot per day spanning the whole window, so a 09:00-12:00 entry produced a single
 * three-hour visit.
 *
 * days_of_week uses Carbon dayOfWeek, 0 = Sunday, which is also what
 * FullCalendar and the admin checkboxes use — one numbering across the feature.
 */
class BulkVisitSlotData extends Data
{
    public function __construct(
        public string $visit_service_id,
        public string $start_date,
        public string $end_date,
        /** @var array<int, int> */
        public array $days_of_week,
        public string $start_time,
        public string $end_time,
        public int $slot_minutes,
        public int $break_minutes,
        public int $capacity,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'visit_service_id' => ['required', 'string', Rule::exists('visit_services', 'id')],

            'start_date' => ['required', 'date_format:Y-m-d'],
            /*
             * A YEAR IS THE CEILING, and it is a real guard rather than taste: the
             * generator writes one row per slot per matching day, so an unbounded
             * range with a 5-minute step is a table of millions written from one
             * click. The controller caps the row count too.
             */
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:'.now()->addYear()->toDateString()],

            'days_of_week' => ['required', 'array', 'min:1', 'max:7'],
            'days_of_week.*' => ['integer', 'between:0,6'],

            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],

            // The step. Not defaulted to the service duration here — the drawer
            // pre-fills it from there, so what gets generated is what was on screen.
            'slot_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            // Changeover time between tours. Zero is a legitimate answer.
            'break_minutes' => ['required', 'integer', 'min:0', 'max:240'],

            'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
