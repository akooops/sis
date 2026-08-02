<?php

namespace App\Models;

use App\Traits\Translations\HasEnabledTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A named bucket of a brand's assets — Visual brand identity → Fonts.
 *
 * `name` is the internal label, `title` the translated public copy, and `order`
 * its position within the brand, written only by the reorder endpoint. It owns
 * its assets: deleting a group takes them with it.
 */
class BrandAssetGroup extends Model
{
    use HasEnabledTranslations, HasFactory, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    /**
     * Stored as locale => value JSON. The trait casts these, so they must NOT
     * also appear in $casts.
     *
     * @var array<int, string>
     */
    public $translatable = ['title'];

    protected $guarded = ['id'];

    protected $casts = [
        'order' => 'integer',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** Ordered on the relation: the assets are a hand-sorted strip, never a set. */
    public function assets(): HasMany
    {
        return $this->hasMany(BrandAsset::class)->orderBy('order');
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /** Display order, created_at breaking ties so it is never arbitrary. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('created_at');
    }

    /**
     * Order is per brand — each brand numbers its groups from 0 — so the max is
     * taken within the brand. Zero-based to match the positions reorder writes.
     */
    public static function nextOrder(?string $brandId): int
    {
        $last = static::query()->where('brand_id', $brandId)->max('order');

        return $last === null ? 0 : (int) $last + 1;
    }
}
