<?php

namespace App\Data\VisitAttendee;

use App\Models\VisitAttendee;
use Spatie\LaravelData\Data;

/** Output DTO for one student on a booking. */
class VisitAttendeeData extends Data
{
    public function __construct(
        public string $id,
        public string $first_name,
        public string $last_name,
        public string $full_name,
        public ?string $grade,
        public ?string $current_school,
        public int $order,
    ) {}

    public static function fromModel(VisitAttendee $attendee): self
    {
        return new self(
            id: $attendee->id,
            first_name: $attendee->first_name,
            last_name: $attendee->last_name,
            full_name: $attendee->full_name,
            // The stored option VALUE (kg1, g7). The admin maps it to a label
            // itself, so the grade a family chose in Arabic reads the same here.
            grade: $attendee->grade,
            current_school: $attendee->current_school,
            order: (int) $attendee->order,
        );
    }
}
