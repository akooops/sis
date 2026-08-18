<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A parent or guardian who has booked at least once.
 *
 * The same split as Candidate/JobApplication: this holds WHO someone is, and
 * VisitReservation holds the fact of one booking. It is what lets a family
 * touring three stages stay one household instead of three rows nobody can tell
 * apart, and it is what makes "has this person already booked this slot?"
 * answerable at all.
 *
 * Deliberately thin — no address, no profile, no notes. A visitor is a contact;
 * everything about a particular visit belongs to the reservation, and so do the
 * children coming along.
 */
class Visitor extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $appends = ['full_name'];

    protected $casts = [];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function reservations(): HasMany
    {
        return $this->hasMany(VisitReservation::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /**
     * The same person, by either identifier.
     *
     * EITHER, NOT BOTH — the same rule as Candidate::scopeIdentifiedBy, and the
     * reason "block the same phone or email on one slot twice" is true rather
     * than merely "block the same email": someone rebooking with the same phone
     * and a retyped address is still the same household. Phone is compared E164,
     * which is what PhoneType::store() already wrote.
     */
    public function scopeIdentifiedBy(Builder $query, ?string $email, ?string $phone): Builder
    {
        return $query->where(function (Builder $q) use ($email, $phone) {
            if ($email !== null && $email !== '') {
                $q->orWhere('email', $email);
            }

            if ($phone !== null && $phone !== '') {
                $q->orWhere('phone', $phone);
            }
        });
    }
}
