<?php

namespace App\Models;

use App\Traits\Translations\HasEnabledTranslations;
use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * One downloadable file inside a brand asset group — a logo variant, a font, a
 * colour sheet. Document crossed with Stream: the file is swappable and the title
 * translated, but it also sits at a position within its group.
 */
class BrandAsset extends Model
{
    use HasEnabledTranslations, HasFactory, HasMedia, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    public const FILE_COLLECTION = 'file';

    /**
     * Stored as locale => value JSON. The trait casts these, so they must NOT
     * also appear in $casts.
     *
     * @var array<int, string>
     */
    public $translatable = ['title'];

    protected $guarded = ['id'];

    protected $appends = ['file_url'];

    protected $casts = [
        'order' => 'integer',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function group(): BelongsTo
    {
        return $this->belongsTo(BrandAssetGroup::class, 'brand_asset_group_id');
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    public function getFileUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl(self::FILE_COLLECTION);
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
     * Order is per group — each group numbers its assets from 0 — so the max is
     * taken within the group. Zero-based to match the positions reorder writes.
     */
    public static function nextOrder(?string $groupId): int
    {
        $last = static::query()->where('brand_asset_group_id', $groupId)->max('order');

        return $last === null ? 0 : (int) $last + 1;
    }

    /**
     * One file: attaching a new one frees the old back to the pool.
     *
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return [self::FILE_COLLECTION];
    }
}
