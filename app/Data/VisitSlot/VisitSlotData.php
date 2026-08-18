<?php

namespace App\Data\VisitSlot;

use App\Models\VisitSlot;
use Spatie\LaravelData\Data;

/**
 * Output DTO for one slot.
 *
 * `reserved`, `remaining` and `state` are COMPUTED, not stored — the model owns
 * that arithmetic (VisitSlot::state) so the admin grid, the public calendar and
 * its legend cannot drift apart. `is_overbooked` exists because the projector
 * deliberately allows the race it documents, and the desk needs to see the result
 * rather than discover it on the day.
 */
class VisitSlotData extends Data
{
    public function __construct(
        public string $id,
        public string $visit_service_id,
        public ?string $service_name,
        public string $starts_at,
        public string $ends_at,
        public int $capacity,
        public bool $is_open,
        public int $reserved,
        public int $remaining,
        public string $state,
        public bool $is_overbooked,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(VisitSlot $slot): self
    {
        $reserved = $slot->reservedCount();

        return new self(
            id: $slot->id,
            visit_service_id: $slot->visit_service_id,
            service_name: $slot->relationLoaded('service') ? $slot->service?->name : null,
            starts_at: $slot->starts_at->toIso8601String(),
            ends_at: $slot->ends_at->toIso8601String(),
            capacity: (int) $slot->capacity,
            is_open: (bool) $slot->is_open,
            reserved: $reserved,
            remaining: $slot->remaining(),
            state: $slot->state(),
            is_overbooked: $reserved > (int) $slot->capacity,
            created_at: $slot->created_at?->toIso8601String(),
            updated_at: $slot->updated_at?->toIso8601String(),
        );
    }
}
