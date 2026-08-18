<?php

namespace App\Observers;

use App\Models\VisitReservation;
use App\Services\Notifications\NotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The audit trail the admissions desk needs: who moved this booking, when, and to
 * what. Every status change lands here as an ordinary updated() diff.
 *
 * It also emits the two visit notifications. OBSERVER-EMITTED, like every other
 * notification in this app — a controller that sent its own would be a second
 * place the rules live, and a status changed by a queue or a console command would
 * announce nothing.
 *
 * A reservation ALSO fires form.submission_received, because it is a form
 * submission. The two visit.* types carry the visit and the household, so the desk
 * subscribes to those and whoever watches forms in general is not drowned in
 * bookings.
 */
class VisitReservationObserver extends BaseObserver
{
    /**
     * A new booking arrived.
     *
     * Announced on CREATE rather than from the projector, so a re-projection of an
     * existing reservation — which is idempotent and routine — never tells the desk
     * about it a second time.
     */
    public function created(Model $model): void
    {
        parent::created($model);

        /** @var VisitReservation $reservation */
        $reservation = $model;

        $this->announce(
            $reservation,
            'visit.reservation_received',
            'New visit booking: '.$this->who($reservation),
            $this->who($reservation).' booked '.$this->visit($reservation).$this->when($reservation).'.',
        );
    }

    /**
     * A status moved.
     *
     * ONLY the status. A reservation is also written when the projector re-runs
     * over it and when somebody saves a note, and a notification for either is
     * noise nobody can act on.
     */
    public function updated(Model $model): void
    {
        parent::updated($model);

        /** @var VisitReservation $reservation */
        $reservation = $model;

        if (! $reservation->wasChanged('status')) {
            return;
        }

        $from = $reservation->getOriginal('status');

        $this->announce(
            $reservation,
            'visit.reservation_status_changed',
            $this->who($reservation).' is now '.$reservation->status,
            $this->who($reservation).' moved from '.$from.' to '.$reservation->status
                .' for '.$this->visit($reservation).$this->when($reservation).'.',
        );
    }

    /**
     * Send, and never let a failure cost the write.
     *
     * The status change is the record; an integration being down must not roll it
     * back or throw out of an observer into whatever triggered it.
     */
    protected function announce(VisitReservation $reservation, string $type, string $title, string $body): void
    {
        try {
            NotificationService::send($type, [
                'title' => $title,
                'body' => $body,
                'route_name' => 'web.admin.visit-reservations.index',
                'route_params' => ['filter[id]' => $reservation->id],
            ]);
        } catch (Throwable $e) {
            Log::channel('integrations')->error('visits.notify-failed', [
                'reservation' => $reservation->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function who(VisitReservation $reservation): string
    {
        return $reservation->visitor?->full_name ?: 'A visitor';
    }

    protected function visit(VisitReservation $reservation): string
    {
        return $reservation->service?->name ?: 'a visit';
    }

    /**
     * When the visit itself is, not when it was booked — the only detail that
     * makes a notification actionable without opening it.
     */
    protected function when(VisitReservation $reservation): string
    {
        $startsAt = $reservation->slot?->starts_at;

        return $startsAt ? ' on '.$startsAt->format('D j M, H:i') : '';
    }

    protected function logName(): string
    {
        return 'visit-reservations';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['visit_slot_id', 'visit_service_id', 'visitor_id', 'status', 'visitors_count'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return [
            'name' => $model->visitor?->full_name ?? $model->visitor_id,
            'visit' => $model->service?->name,
            'status' => (string) $model->status,
        ];
    }
}
