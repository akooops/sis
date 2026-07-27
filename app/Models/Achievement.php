<?php

namespace App\Models;

use App\States\Achievement\AchievementStatus;
use App\States\Achievement\Published;
use App\Traits\Translations\HasEnabledTranslations;
use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\ModelStates\HasStates;
use Spatie\Translatable\HasTranslations;

/**
 * An article that also records when it happened and who did it.
 *
 * Two timelines, easily confused: `published_at` is when the LISTING goes live,
 * `achieved_at` is when the thing happened. A published one is usually long past.
 *
 * `done_by` is translatable — a person or year group reads differently per
 * language.
 */
class Achievement extends Model
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
    public $translatable = ['title', 'description', 'content', 'done_by'];

    protected $guarded = ['id'];

    protected $appends = ['thumbnail_url'];

    protected $casts = [
        'status' => AchievementStatus::class,
        'published_at' => 'datetime',
        // A date, not a datetime: nobody records the hour a prize was won.
        'achieved_at' => 'date',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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

    /** Achievements the public site may serve. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->whereState('status', Published::class);
    }

    /**
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return [self::THUMBNAIL_COLLECTION];
    }
}
