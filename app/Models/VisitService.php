<?php

namespace App\Models;

use App\States\VisitService\Published;
use App\States\VisitService\VisitServiceStatus;
use App\Traits\Translations\HasEnabledTranslations;
use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStates\HasStates;
use Spatie\Translatable\HasTranslations;

/**
 * A bookable school visit — one per stage, in practice.
 *
 * `name` is the internal label; title/description/content are translated into
 * JSON columns. `content` is the long body the public card's Read-more popup
 * shows, which is why css_url/custom_css are here: an admin styles that popup
 * per service without a developer.
 *
 * TWO CLOCKS, easily confused. `status`/`published_at` are editorial — is this
 * visit offered at all. Whether anyone can BOOK is derived from the slots, so a
 * published service whose last slot has passed renders an empty state rather than
 * disappearing, and hiding a service stops new bookings without touching the
 * reservations already made against it.
 */
class VisitService extends Model
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
        'status' => VisitServiceStatus::class,
        'published_at' => 'datetime',
        'duration_minutes' => 'integer',
        'max_visitors' => 'integer',
        'order' => 'integer',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function slots(): HasMany
    {
        return $this->hasMany(VisitSlot::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(VisitReservation::class);
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

    /** Visit services the public site may serve. */
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
     * The upper bound of the party-size counter on the public card.
     *
     * Clamped rather than trusted: a 0 would render a counter nobody can move off
     * and a form whose students group can never be satisfied.
     */
    public function maxVisitors(): int
    {
        return max(1, (int) $this->max_visitors);
    }

    /**
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return [self::THUMBNAIL_COLLECTION];
    }
}
