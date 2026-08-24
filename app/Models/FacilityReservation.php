<?php

namespace App\Models;

use App\States\FacilityReservation\Cancelled;
use App\States\FacilityReservation\FacilityReservationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\ModelStates\HasStates;

/**
 * One act of booking a venue: this person, for this time.
 *
 * THE PERSON IS A `visitors` ROW, SHARED WITH THE VISITS MODULE — the same table
 * a school-tour booking uses, matched on email OR phone. A family that books a
 * tour in September and the hall in November is one contact record, and the desk
 * can see everything they have booked. The columns would have been the same four
 * either way, so a third contacts table would have bought nothing but drift.
 *
 * Simpler than its visits twin in one respect: there is no party size and no
 * attendee children. The brief is a name, an email and a phone, and a slot's
 * limit already counts bookings.
 *
 * DEDUP AND IDEMPOTENCY ARE THE SAME UNIQUE INDEX, (facility_slot_id,
 * visitor_id). It stops one phone or email booking a time twice, and it is the
 * key ReservationProjector re-runs against.
 *
 * facility_id is reachable through the slot and is stored anyway, so the bookings
 * list filters by venue with no join. The projector always writes it from the
 * SLOT, never from the submitted answer, so the two cannot drift.
 */
class FacilityReservation extends Model
{
    use HasFactory, HasStates, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $casts = [
        'status' => FacilityReservationStatus::class,
        'booked_at' => 'datetime',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function slot(): BelongsTo
    {
        return $this->belongsTo(FacilitySlot::class, 'facility_slot_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /**
     * Reservations still holding a seat — WHAT CAPACITY COUNTS.
     *
     * Cancelling is the only thing that gives a seat back. A no-show still
     * occupied it on the day, so it stays counted; re-opening a slot that has
     * already happened helps nobody.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotState('status', Cancelled::class);
    }

    public function isActive(): bool
    {
        return ! $this->status instanceof Cancelled;
    }
}
