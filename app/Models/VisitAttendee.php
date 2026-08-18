<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One student coming on a visit, projected out of the students repeatable group.
 *
 * Typed columns rather than the submission blob, for the same reason
 * candidate_educations exists: "which grades are visiting on Thursday" is a
 * question a JSON answer map cannot answer with an index.
 *
 * grade holds the OPTION VALUE from the form select (kg1, g7), never the
 * translated label, so an Arabic booking and an English one are comparable.
 */
class VisitAttendee extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $appends = ['full_name'];

    protected $casts = [
        'order' => 'integer',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(VisitReservation::class, 'visit_reservation_id');
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
}
