<?php

namespace App\Models;

use App\States\JobOffer\JobOfferStatus;
use App\States\JobOffer\Published;
use App\Traits\Translations\HasEnabledTranslations;
use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStates\HasStates;
use Spatie\Translatable\HasTranslations;

/**
 * A vacancy. `name` is the internal label; title/description/content/address/skills
 * are translated into JSON columns.
 *
 * Two clocks, easily confused: `status`/`published_at` are editorial (is the page
 * live), `deadline_at` is operational (are applications still accepted). Nothing
 * flips status when a deadline passes — closure is derived, see scopeOpen().
 */
class JobOffer extends Model
{
    use HasEnabledTranslations, HasFactory, HasMedia, HasStates, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    public const THUMBNAIL_COLLECTION = 'thumbnail';

    /** Skills are stored joined per locale so the public Blade site can split them. */
    public const SKILLS_SEPARATOR = ';;;';

    /**
     * schema.org employmentType, lowercased — freelance collapses to contractor and
     * apprenticeship to intern, so neither is a value of its own.
     *
     * @var array<int, string>
     */
    public const EMPLOYMENT_TYPES = ['full_time', 'part_time', 'contractor', 'temporary', 'intern', 'volunteer', 'per_diem', 'other'];

    /**
     * @var array<int, string>
     */
    public const WORK_MODES = ['onsite', 'hybrid', 'remote'];

    /**
     * @var array<int, string>
     */
    public const EDUCATION_LEVELS = ['high_school', 'associate', 'bachelor', 'professional_certificate', 'postgraduate'];

    /**
     * The slug of the seeded, always-open posting that catches spontaneous
     * applications. Resolved BY SLUG on the public side, which is what `is_system`
     * exists to protect.
     */
    public const GENERAL_SLUG = 'general-application';

    /**
     * Stored as locale => value JSON. The trait casts these, so they must NOT
     * also appear in $casts.
     *
     * @var array<int, string>
     */
    public $translatable = ['title', 'description', 'content', 'address', 'skills'];

    protected $guarded = ['id'];

    protected $appends = ['thumbnail_url'];

    protected $casts = [
        'status' => JobOfferStatus::class,
        'is_system' => 'bool',
        'published_at' => 'datetime',
        'deadline_at' => 'datetime',
        'start_date' => 'date',
        'experience_years' => 'integer',
        'embedding' => 'array',
        'embedded_at' => 'datetime',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * Every candidate scored against this posting, applied or merely recommended.
     * `->strong()` narrows it to the ones worth showing a manager.
     */
    public function matches(): HasMany
    {
        return $this->hasMany(CandidateMatch::class);
    }

    /* READ-ONLY — see Cluster. Writes go through JobOfferCluster. */
    public function clusters(): BelongsToMany
    {
        return $this->belongsToMany(Cluster::class, 'job_offer_clusters');
    }

    public function clusterLinks(): HasMany
    {
        return $this->hasMany(JobOfferCluster::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl(self::THUMBNAIL_COLLECTION);
    }

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /** Job offers the public site may serve — what the detail page needs. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->whereState('status', Published::class);
    }

    /** Live and still accepting applications — the listing and the apply CTA. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->live()->where(function (Builder $query) {
            $query->whereNull('deadline_at')->orWhere('deadline_at', '>=', now());
        });
    }

    public function isOpen(): bool
    {
        // Same comparison as scopeOpen(), so a row it returns never reports closed.
        return $this->status instanceof Published
            && (! $this->deadline_at || $this->deadline_at->gte(now()));
    }

    /**
     * A seeded posting: undeletable, and its slug is frozen.
     *
     * Same contract `is_system` carries on Page and Form. The general-application
     * flow resolves this row by slug, so a rename would strand every spontaneous
     * applicant; everything else about it stays editable.
     */
    public function isLocked(): bool
    {
        return (bool) $this->is_system;
    }

    /** The always-open posting spontaneous CVs land against. */
    public static function general(): ?self
    {
        return static::query()->where('slug', self::GENERAL_SLUG)->first();
    }

    /**
     * @param  array<int, string>|null  $skills
     */
    public static function joinSkills(?array $skills): string
    {
        return implode(self::SKILLS_SEPARATOR, self::cleanSkills((array) $skills));
    }

    /**
     * @return array<int, string>
     */
    public static function splitSkills(?string $skills): array
    {
        return self::cleanSkills(explode(self::SKILLS_SEPARATOR, (string) $skills));
    }

    /**
     * @param  array<int, mixed>  $skills
     * @return array<int, string>
     */
    protected static function cleanSkills(array $skills): array
    {
        return array_values(array_filter(array_map('trim', $skills), 'strlen'));
    }
}
