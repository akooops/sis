<?php

namespace App\Models;

use App\States\Page\PageStatus;
use App\States\Page\Published;
use App\Traits\Translations\HasEnabledTranslations;
use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\HasStates;
use Spatie\Translatable\HasTranslations;

/**
 * An editorial record with a public slug and a publish workflow.
 *
 * `name` is the internal label; title/description/content are translated into
 * JSON columns. That is content — the UI catalogue is still lang/*.php.
 *
 * One collection, `thumbnail`. Images inserted into the content stay FREE media:
 * nothing prunes them, so nothing needs to own them.
 */
class Page extends Model
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
        'status' => PageStatus::class,
        'published_at' => 'datetime',
        'is_system' => 'bool',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /** Null when unset — no bundled fallback, and it is required at creation. */
    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl(self::THUMBNAIL_COLLECTION);
    }

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /** Pages the public site may serve. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->whereState('status', Published::class);
    }

    /**
     * One thumbnail: a new one frees the old back to the pool.
     *
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return [self::THUMBNAIL_COLLECTION];
    }
}
