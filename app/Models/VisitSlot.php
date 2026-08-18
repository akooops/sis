<?php

namespace App\Models;

use App\States\VisitReservation\Cancelled;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One offered time on one service, with a limit.
 *
 * CAPACITY COUNTS RESERVATIONS, NOT PEOPLE. A booking takes one seat whatever the
 * size of the party. The party size is recorded on the reservation for whoever
 * runs the tour, and the number of people a single booking may bring is bounded
 * separately by VisitService::max_visitors — the two numbers bound different
 * things and collapsing them would make one of them meaningless.
 *
 * is_open is what makes "closed" a real state rather than a euphemism for
 * deleted. A slot with reservations cannot be deleted; closing it stops new
 * bookings while the families already booked keep their time.
 */
class VisitSlot extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    /**
     * The four states a slot can be in, in the order they degrade. This list is
     * the contract the public calendar, its legend and the admin grid all read —
     * see state().
     *
     * @var array<int, string>
     */
    public const STATES = ['open', 'limited', 'full', 'closed'];

    protected $guarded = ['id'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'capacity' => 'integer',
        'is_open' => 'bool',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function service(): BelongsTo
    {
        return $this->belongsTo(VisitService::class, 'visit_service_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(VisitReservation::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /** Slots a visitor could still take: not closed, not in the past. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_open', true)->where('starts_at', '>=', now());
    }

    /**
     * Slots overlapping a window — what the month feed asks for.
     *
     * An OVERLAP, not a containment: a slot running across midnight at the edge
     * of the month belongs to both months' calendars, and testing only starts_at
     * would drop it from one of them.
     */
    public function scopeInRange(Builder $query, mixed $from, mixed $to): Builder
    {
        return $query->where('starts_at', '<', $to)->where('ends_at', '>', $from);
    }

    /**
     * Seats taken.
     *
     * ONLY A CANCELLED RESERVATION GIVES ITS SEAT BACK. Everything else holds
     * one, no-shows included — the seat was genuinely occupied on the day, and
     * re-opening a slot that has already happened helps nobody.
     *
     * Reads the loaded relation when there is one, so a calendar feed that
     * eager-loaded reservations does not fire a query per slot.
     */
    public function reservedCount(): int
    {
        if ($this->relationLoaded('reservations')) {
            return $this->reservations
                ->reject(fn (VisitReservation $reservation) => $reservation->status instanceof Cancelled)
                ->count();
        }

        return (int) $this->reservations()->active()->count();
    }

    public function remaining(): int
    {
        return max(0, (int) $this->capacity - $this->reservedCount());
    }

    public function isFull(): bool
    {
        return $this->remaining() <= 0;
    }

    /**
     * THE ONE PLACE THE PUBLIC STATE IS DECIDED.
     *
     * The month feed, the calendar legend, the Blade empty state and the admin
     * grid all read this, so none of them can disagree about whether a slot is
     * bookable. The "limited" state exists because the stylesheet has always
     * carried .fc-event.is-limited and nothing ever emitted it.
     *
     * @return string one of self::STATES
     */
    public function state(): string
    {
        if (! $this->is_open || $this->starts_at?->isPast()) {
            return 'closed';
        }

        $remaining = $this->remaining();

        if ($remaining <= 0) {
            return 'full';
        }

        return $remaining <= (int) config('visits.limited_at', 2) ? 'limited' : 'open';
    }

    public function isBookable(): bool
    {
        return in_array($this->state(), ['open', 'limited'], true);
    }
}
