<?php

namespace App\Models;

use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

/**
 * A locale the app can hold translations for. The row is metadata only — the
 * translated strings live in lang/{code}/*.php, never in the database.
 *
 * Creating a language creates its folder; renaming the code renames it. Deleting
 * is deliberately NOT symmetric: the files are source and survive the row (see
 * LanguageObserver).
 */
class Language extends Model
{
    use HasFactory, HasMedia, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    /**
     * Fallback flag artwork per seeded code — public/assets/media/flags ships the
     * full ISO set, so a language renders a real flag without anyone uploading
     * one. An uploaded `flag` media always wins.
     *
     * @var array<string, string>
     */
    public const FLAG_ASSETS = [
        'en' => 'united-kingdom',
        'ar' => 'saudi-arabia',
        'fr' => 'france',
        'es' => 'spain',
        'de' => 'germany',
        'it' => 'italy',
        'pt' => 'portugal',
        'ru' => 'russia',
        'hi' => 'india',
    ];

    protected $guarded = ['id'];

    protected $appends = ['flag_url'];

    protected $casts = [
        'is_default' => 'bool',
        'is_rtl' => 'bool',
        'is_enabled' => 'bool',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    public function getFlagUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('flag')
            ?: URL::to('assets/media/flags/'.(static::FLAG_ASSETS[$this->code] ?? 'united-nations').'.svg');
    }

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * A language has one flag: attaching a new one frees the old back into the
     * reusable pool. Deleting a language frees its flag rather than destroying
     * the file — see LanguageObserver.
     *
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return ['flag'];
    }

    /** The one language flagged default — enforced to be unique by the observer. */
    public static function default(): ?self
    {
        return static::query()->where('is_default', true)->first();
    }
}
