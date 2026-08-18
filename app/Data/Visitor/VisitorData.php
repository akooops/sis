<?php

namespace App\Data\Visitor;

use App\Models\Visitor;
use Spatie\LaravelData\Data;

/**
 * Output DTO for a household.
 *
 * `reservations_count` comes from withCount() and is null when it was not asked
 * for — null is "not counted", 0 is "none".
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
        public ?int $reservations_count,
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
            reservations_count: isset($visitor->reservations_count) ? (int) $visitor->reservations_count : null,
            created_at: $visitor->created_at?->toIso8601String(),
            updated_at: $visitor->updated_at?->toIso8601String(),
        );
    }
}
