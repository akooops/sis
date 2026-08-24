<?php

namespace App\Data\Visitor;

use App\Models\Visitor;
use Spatie\LaravelData\Data;

/**
 * Output DTO for a household.
 *
 * The two counts come from withCount() and are null when they were not asked for
 * — null is "not counted", 0 is "none". They are named per module because a
 * visitor books both school tours and venues, and a single `reservations_count`
 * would silently mean one of them.
 */
class VisitorData extends Data
{
    public function __construct(
        public string $id,
        public string $first_name,
        public string $last_name,
        public string $full_name,
        public string $email,
        public ?string $phone,
        public ?int $visit_reservations_count,
        public ?int $facility_reservations_count,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Visitor $visitor): self
    {
        return new self(
            id: $visitor->id,
            first_name: $visitor->first_name,
            last_name: $visitor->last_name,
            full_name: $visitor->full_name,
            email: $visitor->email,
            phone: $visitor->phone,
            visit_reservations_count: isset($visitor->visit_reservations_count) ? (int) $visitor->visit_reservations_count : null,
            facility_reservations_count: isset($visitor->facility_reservations_count) ? (int) $visitor->facility_reservations_count : null,
            created_at: $visitor->created_at?->toIso8601String(),
            updated_at: $visitor->updated_at?->toIso8601String(),
        );
    }
}
