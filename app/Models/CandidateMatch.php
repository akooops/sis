<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How well one candidate fits one posting.
 *
 * ONE TABLE FOR BOTH DIRECTIONS. The candidate drawer reads it filtered by
 * candidate ("other postings this person suits"); the posting drawer reads it
 * filtered by job offer ("good people we already have"). Whether they actually
 * applied is a separate fact, held by JobApplication — so a strong match on a
 * posting someone has NOT applied to is exactly the recommendation HR wants, and
 * needs no second table to express.
 */
class CandidateMatch extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    /** Below this a match is not worth putting in front of anyone. */
    public const SHORTLIST_THRESHOLD = 60;

    protected $guarded = ['id'];

    protected $casts = [
        'score' => 'integer',
        'matched_at' => 'datetime',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function jobOffer(): BelongsTo
    {
        return $this->belongsTo(JobOffer::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /**
     * Matches worth showing.
     *
     * `whereNotNull` as well as the threshold: an unscored row means the queue
     * has not reached it yet, which is not the same as a poor fit and must not be
     * presented as one.
     */
    public function scopeStrong(Builder $query, ?int $threshold = null): Builder
    {
        return $query->whereNotNull('score')->where('score', '>=', $threshold ?? self::SHORTLIST_THRESHOLD);
    }

    public function scopeUnscored(Builder $query): Builder
    {
        return $query->whereNull('score');
    }
}
