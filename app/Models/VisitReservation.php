<?php

namespace App\Models;

use App\States\VisitReservation\Cancelled;
use App\States\VisitReservation\VisitReservationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStates\HasStates;

/**
 * One act of booking: this household, for this slot.
 *
 * The visitor holds WHO they are; this holds the fact of the booking and where it
 * has reached at the admissions desk. The children coming along are attendees of
 * THIS row, not of the visitor — a family brings different children to different
 * tours, and only the guardian carries between them.
 *
 * DEDUP AND IDEMPOTENCY ARE THE SAME UNIQUE INDEX, (visit_slot_id, visitor_id).
 * It is what stops one phone or email booking a slot twice, and it is the key
 * ReservationProjector re-runs against, so re-projecting a submission updates the
 * booking instead of making a second one.
 *
 * visit_service_id is reachable through the slot and is stored anyway, so the
 * reservations list filters by service with no join — the same redundancy
 * job_applications carries. The projector always writes it from the SLOT, never
 * from the submitted answer, so the two cannot drift.
 */
class VisitReservation extends Model
{
    use HasFactory, HasStates, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $casts = [
        'status' => VisitReservationStatus::class,
        'booked_at' => 'datetime',
        'visitors_count' => 'integer',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function slot(): BelongsTo
    {
        return $this->belongsTo(VisitSlot::class, 'visit_slot_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(VisitService::class, 'visit_service_id');
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(VisitAttendee::class)->orderBy('order');
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
