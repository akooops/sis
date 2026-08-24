<?php

namespace App\Data\FacilitySlot;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Editing one slot.
 *
 * The SERVICE IS NOT EDITABLE. Moving a slot between services would carry its
 * reservations with it and silently change what a family booked; deleting an
 * empty slot and making another is the honest way to do that.
 *
 * The capacity floor — it may not drop below the seats already taken — is
 * enforced in the controller rather than here, because it needs the slot the
 * route bound and its live reservation count.
 */
class UpdateFacilitySlotData extends Data
{
    public function __construct(
        public string $starts_at,
        public string $ends_at,
        public int $capacity,
        public bool $is_open = true,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
            'is_open' => ['sometimes', 'boolean'],
        ];
    }
}
