<?php

namespace App\Data\FacilityReservation;

use App\Data\FacilitySlot\FacilitySlotData;
use App\Data\Visitor\VisitorData;
use App\Models\FacilityReservation;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * Output DTO for one venue booking.
 *
 * The person's name, email and phone are FLATTENED onto the row as well as being
 * available through the lazy relation, because the index renders all three in one
 * column and eager-loading the relation only to reach into it would make the list
 * page depend on an include the drawer needs and the table does not — the same
 * shape VisitReservationData and JobApplicationData use.
 */
class FacilityReservationData extends Data
{
    public function __construct(
        public string $id,
        public string $facility_slot_id,
        public string $facility_id,
        public string $visitor_id,
        public ?string $form_submission_id,
        public string $status,
        public ?string $booked_at,
        public ?string $note,
        public ?string $visitor_name,
        public ?string $visitor_email,
        public ?string $visitor_phone,
        public ?string $facility_name,
        public ?string $slot_starts_at,
        public ?string $slot_ends_at,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|VisitorData|null $visitor,
        public Lazy|FacilitySlotData|null $slot,
    ) {}

    public static function fromModel(FacilityReservation $reservation): self
    {
        $visitor = $reservation->relationLoaded('visitor') ? $reservation->visitor : null;
        $slot = $reservation->relationLoaded('slot') ? $reservation->slot : null;
        $facility = $reservation->relationLoaded('facility') ? $reservation->facility : null;

        return new self(
            id: $reservation->id,
            facility_slot_id: $reservation->facility_slot_id,
            facility_id: $reservation->facility_id,
            visitor_id: $reservation->visitor_id,
            form_submission_id: $reservation->form_submission_id,
            status: $reservation->status->getValue(),
            booked_at: $reservation->booked_at?->toIso8601String(),
            note: $reservation->note,
            visitor_name: $visitor?->full_name,
            visitor_email: $visitor?->email,
            visitor_phone: $visitor?->phone,
            facility_name: $facility?->name,
            slot_starts_at: $slot?->starts_at?->toIso8601String(),
            slot_ends_at: $slot?->ends_at?->toIso8601String(),
            created_at: $reservation->created_at?->toIso8601String(),
            updated_at: $reservation->updated_at?->toIso8601String(),
            visitor: Lazy::whenLoaded('visitor', $reservation, fn () => $reservation->visitor ? VisitorData::from($reservation->visitor) : null),
            slot: Lazy::whenLoaded('slot', $reservation, fn () => $reservation->slot ? FacilitySlotData::from($reservation->slot) : null),
        );
    }
}
