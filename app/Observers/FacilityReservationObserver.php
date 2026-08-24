<?php

namespace App\Observers;

use App\Models\FacilityReservation;
use App\Services\Notifications\NotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The audit trail the desk needs for venue bookings: who moved this one, when,
 * and to what. Every status change lands here as an ordinary updated() diff.
 *
 * It also emits the two facility notifications. OBSERVER-EMITTED, like every
 * other notification in this app — a controller that sent its own would be a
 * second place the rules live, and a status changed by a queue or a console
 * command would announce nothing.
 *
 * A booking ALSO fires form.submission_received, because it is a form submission.
 * The two facility.* types carry the venue and the person, so whoever runs the
 * venues subscribes to those and whoever watches forms in general is not drowned.
 */
class FacilityReservationObserver extends BaseObserver
{
    /**
     * A new booking arrived.
     *
     * Announced on CREATE rather than from the projector, so a re-projection of an
     * existing booking — which is idempotent and routine — never announces it a
     * second time.
     */
    public function created(Model $model): void
    {
        parent::created($model);

        /** @var FacilityReservation $reservation */
        $reservation = $model;

        $this->announce(
            $reservation,
            'facility.reservation_received',
            'New venue booking: '.$this->who($reservation),
            $this->who($reservation).' booked '.$this->venue($reservation).$this->when($reservation).'.',
        );
    }

    /**
     * A status moved.
     *
     * ONLY the status. A booking is also written when the projector re-runs over
     * it and when somebody saves a note, and a notification for either is noise
     * nobody can act on.
     */
    public function updated(Model $model): void
    {
        parent::updated($model);

        /** @var FacilityReservation $reservation */
        $reservation = $model;

        if (! $reservation->wasChanged('status')) {
            return;
        }

        $from = $reservation->getOriginal('status');

        $this->announce(
            $reservation,
            'facility.reservation_status_changed',
            $this->who($reservation).' is now '.$reservation->status,
            $this->who($reservation).' moved from '.$from.' to '.$reservation->status
                .' for '.$this->venue($reservation).$this->when($reservation).'.',
        );
    }

    /**
     * Send, and never let a failure cost the write.
     *
     * The status change is the record; an integration being down must not roll it
     * back or throw out of an observer into whatever triggered it.
     */
    protected function announce(FacilityReservation $reservation, string $type, string $title, string $body): void
    {
        try {
            NotificationService::send($type, [
                'title' => $title,
                'body' => $body,
                'route_name' => 'web.admin.facility-reservations.index',
                'route_params' => ['filter[id]' => $reservation->id],
            ]);
        } catch (Throwable $e) {
            Log::channel('integrations')->error('facilities.notify-failed', [
                'reservation' => $reservation->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function who(FacilityReservation $reservation): string
    {
        return $reservation->visitor?->full_name ?: 'Someone';
    }

    protected function venue(FacilityReservation $reservation): string
    {
        return $reservation->facility?->name ?: 'a venue';
    }

    /**
     * When the booking itself is, not when it was made — the only detail that
     * makes a notification actionable without opening it.
     */
    protected function when(FacilityReservation $reservation): string
    {
        $startsAt = $reservation->slot?->starts_at;

        return $startsAt ? ' on '.$startsAt->format('D j M, H:i') : '';
    }

    protected function logName(): string
    {
        return 'facility-reservations';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['facility_slot_id', 'facility_id', 'visitor_id', 'status'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return [
            'name' => $model->visitor?->full_name ?? $model->visitor_id,
            'facility' => $model->facility?->name,
            'status' => (string) $model->status,
        ];
    }
}
