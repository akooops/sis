<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A person who has applied at least once.
 *
 * The candidate holds WHO someone is and what they can do; JobApplication holds
 * the fact of one application. Separating them is what lets the same human apply
 * to three postings without becoming three records, and it is what makes "this
 * candidate's other good matches" and "this posting's good candidates" the same
 * query from two ends.
 *
 * Its profile is a PROJECTION of the most recent application, not a second copy
 * of the truth — the raw answers stay on the FormSubmission and the projector can
 * be re-run over them at any time.
 */
class Candidate extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    /**
     * How well someone speaks a language. A plain list rather than a database
     * enum, so adding a level is a deploy rather than an ALTER on a live table.
     *
     * @var array<int, string>
     */
    public const PROFICIENCIES = ['basic', 'intermediate', 'advanced', 'native'];

    /**
     * How many dimensions we ask the embedding API for.
     *
     * SMALL ON PURPOSE. MySQL has no vector type, so these live in a JSON column
     * and every comparison runs in PHP; 256 keeps a few thousand candidates
     * clusterable in seconds where the full 1536 would not.
     */
    public const EMBEDDING_DIMENSIONS = 256;

    protected $guarded = ['id'];

    protected $appends = ['full_name'];

    protected $casts = [
        'embedding' => 'array',
        'summarised_at' => 'datetime',
        'embedded_at' => 'datetime',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    /** Nationality. */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * The CV, POINTED AT rather than owned: the file belongs to the form
     * submission that carried it, stays scanned, and is reachable only through a
     * signed route. This column only says which one is current.
     */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cv_media_id');
    }

    public function educations(): HasMany
    {
        return $this->hasMany(CandidateEducation::class)->orderBy('order');
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(CandidateExperience::class)->orderBy('order');
    }

    public function languages(): HasMany
    {
        return $this->hasMany(CandidateLanguage::class)->orderBy('order');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(CandidateSkill::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(CandidateMatch::class);
    }

    /*
     * READ-ONLY, like Form's blocked-country and notification-group relations.
     * Do not call attach/detach/sync: candidate_clusters carries its own ULID
     * primary key with no database default, and attach() writes a raw insert that
     * never runs the model — it fails with "Field 'id' doesn't have a default
     * value". Writes go through CandidateCluster, which also makes its observer
     * fire so the change is audited against this candidate.
     */
    public function clusters(): BelongsToMany
    {
        return $this->belongsToMany(Cluster::class, 'candidate_clusters');
    }

    /** The pivot rows themselves — the write path. */
    public function clusterLinks(): HasMany
    {
        return $this->hasMany(CandidateCluster::class);
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
     * EITHER, NOT BOTH: someone who applies twice with the same email but a
     * retyped phone number is one candidate, and requiring both to match would
     * quietly create a second record for them. Phone is compared E164, which is
     * what PhoneType::store() already wrote.
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

    /** Candidates that can take part in clustering and matching. */
    public function scopeEmbedded(Builder $query): Builder
    {
        return $query->whereNotNull('embedding');
    }

    /** Years of experience, counting a current role up to today. */
    public function yearsOfExperience(): int
    {
        $now = (int) now()->year;

        return (int) $this->experiences
            ->filter(fn (CandidateExperience $e) => $e->start_year !== null)
            ->sum(fn (CandidateExperience $e) => max(0, ($e->is_current ? $now : ($e->end_year ?? $now)) - $e->start_year));
    }
}
