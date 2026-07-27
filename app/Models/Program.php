<?php

namespace App\Models;

use App\Traits\Translations\HasEnabledTranslations;
use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A programme of study. It owns its streams and grades — deleting one takes them
 * with it.
 *
 * `name` is the internal label, `slug` the public URL. `order` is the display
 * position and is only written by the reorder endpoint.
 */
class Program extends Model
{
    use HasEnabledTranslations, HasFactory, HasMedia, HasTranslations, HasUlids;

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
    public $translatable = ['title', 'subtitle', 'description', 'content'];

    protected $guarded = ['id'];

    protected $appends = ['thumbnail_url'];

    protected $casts = [
        'order' => 'integer',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function streams(): HasMany
    {
        return $this->hasMany(Stream::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
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

    /** Display order, created_at breaking ties so it is never arbitrary. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('created_at');
    }

    /**
     * A new program goes on the end. Zero-based to match the positions reorder
     * writes, or the first ever program would sit at 1 until someone dragged.
     */
    public static function nextOrder(): int
    {
        $last = static::query()->max('order');

        return $last === null ? 0 : (int) $last + 1;
    }

    /**
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return [self::THUMBNAIL_COLLECTION];
    }
}
