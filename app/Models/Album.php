<?php

namespace App\Models;

use App\States\Album\AlbumStatus;
use App\States\Album\Published;
use App\Traits\Translations\HasEnabledTranslations;
use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\HasStates;
use Spatie\Translatable\HasTranslations;

/**
 * An article that also carries an ordered gallery.
 *
 * `thumbnail` is the cover (one file). `files` is the album — images, video and
 * audio, ordered by media.order_column, which UploadService::sync() writes from
 * the submitted order.
 *
 * Images inserted into the content are NOT a collection: they stay free media.
 */
class Album extends Model
{
    use HasEnabledTranslations, HasFactory, HasMedia, HasStates, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    public const THUMBNAIL_COLLECTION = 'thumbnail';

    public const FILES_COLLECTION = 'files';

    /**
     * Mirrors the keys in config('uploads.types'). Documents are excluded: an album
     * is something you look at or listen to.
     *
     * @var array<int, string>
     */
    public const FILE_TYPES = ['images', 'videos', 'audio'];

    /**
     * @var array<int, string>
     */
    public $translatable = ['title', 'description', 'content'];

    protected $guarded = ['id'];

    protected $appends = ['thumbnail_url'];

    protected $casts = [
        'status' => AlbumStatus::class,
        'published_at' => 'datetime',
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

    /** Albums the public site may serve. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->whereState('status', Published::class);
    }

    /**
     * Only the cover is single-file; `files` is absent, which is what makes it
     * multi-file.
     *
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return [self::THUMBNAIL_COLLECTION];
    }
}
