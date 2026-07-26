<?php

namespace App\Models;

use App\States\Event\EventStatus;
use App\States\Event\Published;
use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\HasStates;
use Spatie\Translatable\HasTranslations;

/**
 * A dated event: an article that also says when it happens.
 *
 * Two unrelated timelines, and confusing them is the easy mistake here.
 * `published_at` is when the LISTING goes live; `start_at`/`end_at` are when the
 * event itself runs. A published event can be long past, and a draft event can
 * start tomorrow — the publish pipeline never looks at start_at.
 */
class Event extends Model
{
    use HasFactory, HasMedia, HasStates, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    public const THUMBNAIL_COLLECTION = 'thumbnail';

    public const IMAGES_COLLECTION = 'images';

    /**
     * @var array<int, string>
     */
    public $translatable = ['title', 'description', 'content'];

    protected $guarded = ['id'];

    protected $appends = ['thumbnail_url'];

    protected $casts = [
        'status' => EventStatus::class,
        'published_at' => 'datetime',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

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

    /** Events the public site may serve. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->whereState('status', Published::class);
    }

    /** Still to come, or running right now. */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('end_at', '>=', now());
    }

    /**
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return [self::THUMBNAIL_COLLECTION];
    }
}
