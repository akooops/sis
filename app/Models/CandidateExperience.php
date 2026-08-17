<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One role the candidate has held.
 *
 * `is_current` is kept separate from a missing end year because "still there"
 * and "did not say" are different answers, and only the first should count
 * toward years of experience.
 */
class CandidateExperience extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $casts = [
        'start_year' => 'integer',
        'end_year' => 'integer',
        'is_current' => 'bool',
        'order' => 'integer',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/
}
