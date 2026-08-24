<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

/**
 * Venue time slots ARE audited, for the reason VisitSlotObserver gives.
 *
 * The bulk generator writes with insertOrIgnore, which fires no model events, so
 * a term of two hundred slots costs zero audit rows. What lands here is the
 * deliberate acts a person takes afterwards: closing a slot, moving one, changing
 * a limit, deleting one — each of which changes what somebody can book.
 */
class FacilitySlotObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'facility-slots';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['facility_id', 'starts_at', 'ends_at', 'capacity'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return [
            'name' => $model->starts_at?->format('D j M Y, H:i') ?? $model->getKey(),
            'facility' => $model->facility?->name,
        ];
    }
}
