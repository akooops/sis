<?php

namespace App\Data\VisitSlot;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * One slot, created by hand or by dragging a range on the admin calendar.
 *
 * NO DUPLICATE RULE HERE. The unique index on (service, starts_at, ends_at) is the
 * guarantee, and a Rule::unique restating it would have to compare a raw payload
 * string against a DATETIME column — which works until the day a client sends
 * seconds and the two stop matching. The controller catches the constraint and
 * turns it into a readable message instead, the same way SubmitController adopts a
 * losing insert rather than pre-checking for one.
 */
class StoreVisitSlotData extends Data
{
    public function __construct(
        public string $visit_service_id,
        public string $starts_at,
        public string $ends_at,
        public int $capacity,
        public bool $is_open = true,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'visit_service_id' => ['required', 'string', Rule::exists('visit_services', 'id')],

            'starts_at' => ['required', 'date'],
            // after, not after_or_equal: a zero-length slot is not a visit, and
            // FullCalendar draws one as an event nobody can click.
            'ends_at' => ['required', 'date', 'after:starts_at'],

            // 0 would be a slot that exists and can never be booked. Closing it is
            // what is_open is for, and it says so on the calendar.
            'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
            'is_open' => ['sometimes', 'boolean'],
        ];
    }
}
