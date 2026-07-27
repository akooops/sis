<?php

namespace App\Models;

use App\Traits\Translations\HasEnabledTranslations;
use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Spatie\Translatable\HasTranslations;

/**
 * An ISO 3166-1 country. Reference data, seeded from config/countries.php.
 *
 * `name` is the internal English label and is never translated; `title` is the
 * public name and `nationality` the demonym (British / بريطاني).
 */
class Country extends Model
{
    use HasEnabledTranslations, HasFactory, HasMedia, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    public const FLAG_COLLECTION = 'flag';

    /** Stands in when a territory has no bundled artwork of its own. */
    public const FLAG_FALLBACK = 'united-nations';

    /**
     * Stored as locale => value JSON. The trait casts these, so they must NOT
     * also appear in $casts.
     *
     * @var array<int, string>
     */
    public $translatable = ['title', 'nationality'];

    protected $guarded = ['id'];

    protected $appends = ['flag_url'];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /**
     * The `flag` column, never the code: the bundled filenames are name-slugs
     * (saudi-arabia, united-kingdom), some are stale vs ISO (swaziland) and the
     * set is not 1:1 with ISO. An uploaded flag always wins.
     */
    public function getFlagUrlAttribute(): string
    {
        return $this->getFirstMediaUrl(self::FLAG_COLLECTION)
            ?: URL::to('assets/media/flags/'.($this->flag ?: self::FLAG_FALLBACK).'.svg');
    }

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * One flag: attaching a new one frees the old back to the pool.
     *
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return [self::FLAG_COLLECTION];
    }
}
