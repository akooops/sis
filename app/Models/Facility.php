<?php

namespace App\Models;

use App\States\Facility\FacilityStatus;
use App\States\Facility\Published;
use App\Traits\Translations\HasEnabledTranslations;
use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStates\HasStates;
use Spatie\Translatable\HasTranslations;

/**
 * A venue the school rents out.
 *
 * `name` is the internal label; title/description/content are translated into
 * JSON columns, and css_url/custom_css style the body an admin writes — the same
 * pair Article, Page and VisitService carry.
 *
 * ARTICLES AND ALBUMS ARE ATTACHED, NOT OWNED. Both are ordinary site content
 * that keeps its own URL and its own listing; a venue merely says "these ones are
 * about me". The old module inverted that with a facility_id column on the
 * articles table, which made every main-site query responsible for remembering a
 * scope it could silently forget.
 *
 * Two clocks, as on VisitService: status/published_at are editorial, while
 * whether anyone can BOOK is derived from the slots — so hiding a venue stops new
 * bookings without touching the ones already made against it.
 */
class Facility extends Model
{
    use HasEnabledTranslations, HasFactory, HasMedia, HasStates, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    public const THUMBNAIL_COLLECTION = 'thumbnail';

    /**
     * Stored as locale => value JSON. The trait casts these, so they must NOT
     * also appear in $casts.
     *
     * @var array<int, string>
     */
    public $translatable = ['title', 'description', 'content'];

    protected $guarded = ['id'];

    protected $appends = ['thumbnail_url'];

    protected $casts = [
        'status' => FacilityStatus::class,
        'published_at' => 'datetime',
        'order' => 'integer',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function slots(): HasMany
    {
        return $this->hasMany(FacilitySlot::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(FacilityReservation::class);
    }

    /*
     * READ-ONLY. Do not call attach/detach/sync on either of these.
     *
     * Both pivot tables carry their own ULID primary key with no database
     * default, and attach() writes a raw insert that never runs the model — so it
     * fails outright with "Field 'id' doesn't have a default value". The relations
     * here exist for querying and eager loading.
     *
     * Writes go through the pivot MODELS instead (articleLinks, albumLinks),
     * which is also what makes their observers fire so the change is audited
     * against this facility.
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'facility_articles');
    }

    public function albums(): BelongsToMany
    {
        return $this->belongsToMany(Album::class, 'facility_albums');
    }

    /** The pivot rows themselves — the write path. */
    public function articleLinks(): HasMany
    {
        return $this->hasMany(FacilityArticle::class);
    }

    public function albumLinks(): HasMany
    {
        return $this->hasMany(FacilityAlbum::class);
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

    /** Facilities the public site may serve. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->whereState('status', Published::class);
    }

    /** Live AND with at least one slot somebody could still take. */
    public function scopeBookable(Builder $query): Builder
    {
        return $query->live()->whereHas('slots', fn (Builder $slots) => $slots->open());
    }

    /**
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return [self::THUMBNAIL_COLLECTION];
    }
}
