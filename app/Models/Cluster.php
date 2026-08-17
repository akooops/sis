<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A talent pool, DISCOVERED rather than declared.
 *
 * Nobody knows the categories in advance, so these are not seeded: a nightly job
 * clusters candidate embeddings and an LLM names each result. `is_locked` stops
 * the next rebuild renaming one an admin has titled by hand.
 *
 * The point of them is to BOUND THE MATCH MATRIX. Scoring every candidate against
 * every open posting is quadratic and every cell is an LLM call; scoring only
 * within a shared cluster keeps it linear.
 */
class Cluster extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    /**
     * Below this many embedded candidates, clustering is noise dressed up as
     * insight — the rebuild stands down and matching falls back to every open
     * posting, which is cheap at that volume anyway.
     */
    public const MIN_CANDIDATES = 30;

    protected $guarded = ['id'];

    protected $casts = [
        'centroid' => 'array',
        'size' => 'integer',
        'is_locked' => 'bool',
        'rebuilt_at' => 'datetime',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    /*
     * READ-ONLY. Never attach/detach/sync — both pivots carry their own ULID
     * primary key with no database default, so a raw pivot insert fails outright.
     * Writes go through CandidateCluster / JobOfferCluster.
     */

    public function candidates(): BelongsToMany
    {
        return $this->belongsToMany(Candidate::class, 'candidate_clusters');
    }

    public function jobOffers(): BelongsToMany
    {
        return $this->belongsToMany(JobOffer::class, 'job_offer_clusters');
    }

    /** The pivot rows themselves — the write path. */
    public function candidateLinks(): HasMany
    {
        return $this->hasMany(CandidateCluster::class);
    }

    public function jobOfferLinks(): HasMany
    {
        return $this->hasMany(JobOfferCluster::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /** Clusters a rebuild may rename. A hand-titled one keeps its name. */
    public function scopeRenameable(Builder $query): Builder
    {
        return $query->where('is_locked', false);
    }
}
