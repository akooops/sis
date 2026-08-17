<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * JobOffer <-> Cluster membership, as a first-class row.
 *
 * A MODEL RATHER THAN attach()/sync(), for the reason every pivot here is one:
 * the table has a ULID primary key with no database default, and attach() writes
 * a raw insert that never runs the model — it fails with "Field 'id' doesn't have
 * a default value". Going through the model is also what makes the observer fire,
 * so the membership change is audited against the job offer.
 */
class JobOfferCluster extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $casts = [
        'distance' => 'float',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function jobOffer(): BelongsTo
    {
        return $this->belongsTo(JobOffer::class);
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/
}
