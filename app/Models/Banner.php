<?php

namespace App\Models;

use App\Enums\MorphType;
use App\States\Banner\BannerStatus;
use App\States\Banner\Published;
use App\Traits\Translations\HasEnabledTranslations;
use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\ModelStates\HasStates;
use Spatie\Translatable\HasTranslations;

/**
 * A hero banner. It links nowhere, to an external `url`, or to any internal
 * record with a public slug (`linkable`) — the two are mutually exclusive.
 *
 * `name` is the internal label; `order` is the display position and is only
 * written by the reorder endpoint.
 *
 * `status` is the publish workflow every content module shares — only a
 * published banner reaches the carousel. It is independent of `order`: a hidden
 * banner keeps its position so re-publishing puts it back where it was.
 */
class Banner extends Model
{
    use HasEnabledTranslations, HasFactory, HasMedia, HasStates, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    public const THUMBNAIL_COLLECTION = 'thumbnail';

    public const VIDEO_COLLECTION = 'video';

    /**
     * MorphType aliases a banner may link to: every model with a public slug.
     *
     * @var array<int, string>
     */
    public const LINKABLE_TYPES = [
        'page',
        'article',
        'achievement',
        'album',
        'event',
        'job_offer',
        'program',
        'stream',
        'form',
    ];

    /**
     * Stored as locale => value JSON. The trait casts these, so they must NOT
     * also appear in $casts.
     *
     * @var array<int, string>
     */
    public $translatable = ['title', 'cta'];

    protected $guarded = ['id'];

    protected $appends = ['thumbnail_url', 'video_url'];

    protected $casts = [
        'order' => 'integer',
        'status' => BannerStatus::class,
        'published_at' => 'datetime',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    /** Stores a fully qualified class name, like every other morph column here. */
    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl(self::THUMBNAIL_COLLECTION);
    }

    public function getVideoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl(self::VIDEO_COLLECTION);
    }

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /** Banners the public site may serve. Pair with ordered() for the carousel. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->whereState('status', Published::class);
    }

    /** Display order, created_at breaking ties so it is never arbitrary. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('created_at');
    }

    /**
     * A new banner goes on the end. Zero-based to match the positions reorder
     * writes, or the first ever banner would sit at 1 until someone dragged.
     */
    public static function nextOrder(): int
    {
        $last = static::query()->max('order');

        return $last === null ? 0 : (int) $last + 1;
    }

    /** The table an alias points at, for the linkable_id exists rule. */
    public static function linkableTable(?string $alias): ?string
    {
        if (! in_array($alias, self::LINKABLE_TYPES, true)) {
            return null;
        }

        $class = MorphType::classFor($alias);

        return $class === null ? null : (new $class)->getTable();
    }

    /**
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return [self::THUMBNAIL_COLLECTION, self::VIDEO_COLLECTION];
    }
}
