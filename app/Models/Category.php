<?php

namespace App\Models;

use App\Traits\Translations\HasEnabledTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A classification, shared by every kind of content — one flat list.
 *
 * Two labels: `name` is internal and never translated, `title` is the public
 * copy and is. `color` is the chip background on the public site.
 */
class Category extends Model
{
    use HasEnabledTranslations, HasFactory, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    public const DEFAULT_COLOR = '#1B84FF';

    /**
     * Stored as locale => value JSON. The trait casts these, so they must NOT
     * also appear in $casts.
     *
     * @var array<int, string>
     */
    public $translatable = ['title'];

    protected $guarded = ['id'];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class);
    }

    public function jobOffers(): HasMany
    {
        return $this->hasMany(JobOffer::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /**
     * The fallback: what an unfiled record gets, and where a deleted category's
     * content moves. Seeded and undeletable, so null means unseeded.
     */
    public static function default(): ?self
    {
        return static::query()->where('is_default', true)->first();
    }
}
