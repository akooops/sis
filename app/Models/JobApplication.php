<?php

namespace App\Models;

use App\States\JobApplication\JobApplicationStatus;
use App\States\JobApplication\Rejected;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStates\HasStates;

/**
 * One act of applying: this person, to this posting.
 *
 * Deliberately THIN. Everything about the applicant is on Candidate and
 * everything about the fit is on CandidateMatch — this row carries only the fact
 * that it happened and where HR has taken it. That is what keeps a candidate who
 * applies to three postings from having three divergent copies of their CV data,
 * and what stops an application's score drifting from the recommendation engine's.
 */
class JobApplication extends Model
{
    use HasFactory, HasStates, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $casts = [
        'status' => JobApplicationStatus::class,
        'applied_at' => 'datetime',
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

    /**
     * The raw answers this was projected from.
     *
     * Nullable and nullOnDelete: it is an audit trail, not the record of record.
     * Everything HR reads has already been projected into typed columns.
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }

    /**
     * Every score this application's candidate has, across all postings.
     *
     * The eager-loadable half of the pair below: `with('candidateMatches')` on a
     * list, then match() per row costs no extra query.
     */
    public function candidateMatches(): HasMany
    {
        return $this->hasMany(CandidateMatch::class, 'candidate_id', 'candidate_id');
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /** Applications still in play — everything that is not a closed rejection. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotState('status', Rejected::class);
    }

    /**
     * THE SCORE for the posting this application is against.
     *
     * A METHOD, NOT A RELATION, and that is forced rather than chosen. The row is
     * identified by TWO columns — candidate AND job offer — and Eloquent has no
     * composite-key relation: a `hasOne(...)->whereColumn(…, 'job_applications.
     * job_offer_id')` compiles, but the parent table is not in the subquery's
     * scope, so it fails at runtime with "Unknown column
     * job_applications.job_offer_id". Constraining with `$this->job_offer_id`
     * instead breaks eager loading, because Laravel builds the constraint from a
     * blank instance whose id is null.
     *
     * Reads from `candidateMatches` when it has been eager-loaded, so a list does
     * not pay a query per row.
     *
     * Kept here rather than duplicated onto the application because the score IS
     * the recommendation engine's — one scorer, one number, nothing to reconcile.
     */
    public function match(): ?CandidateMatch
    {
        if ($this->relationLoaded('candidateMatches')) {
            return $this->candidateMatches->firstWhere('job_offer_id', $this->job_offer_id);
        }

        return CandidateMatch::query()
            ->where('candidate_id', $this->candidate_id)
            ->where('job_offer_id', $this->job_offer_id)
            ->first();
    }
}
