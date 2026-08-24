<?php

namespace App\Data\FacilityReservation;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * THE NOTE, AND NOTHING ELSE.
 *
 * A reservation is a record of what somebody asked for, so the only thing an
 * admin may edit on it is the desk's own note. Everything that matters about it
 * moves through a state transition with its own endpoint, its own permission and
 * its own audit row — which is the whole reason there is no general update here.
 */
class UpdateFacilityReservationData extends Data
{
    public function __construct(
        public ?string $note,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
