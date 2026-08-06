<?php

namespace App\Models;

use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

/**
 * A locale. Metadata only — the strings live in lang/{code}/*.php.
 *
 * Creating one creates its folder and renaming the code renames it. Deleting is
 * NOT symmetric: the files are source and survive the row (LanguageObserver).
 */
class Language extends Model
{
    use HasFactory, HasMedia, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    /**
     * Fallback artwork per seeded code — public/assets/admin/media/flags ships the full
     * ISO set, so a language renders a flag with nothing uploaded. An uploaded
     * `flag` media always wins.
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
            ?: URL::to('assets/admin/media/flags/'.(static::FLAG_ASSETS[$this->code] ?? 'united-nations').'.svg');
    }

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * One flag: attaching a new one frees the old back to the pool. Deleting a
     * language frees its flag rather than destroying the file.
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

    /**
     * Enabled locale codes, memoised for the request.
     *
     * Two callers need this on one request: the translated search filter, and every
     * translatable DTO's fromModel() — that second one is the N+1 this prevents. A
     * static memo, not a Cache:: layer: this app has none (see CLAUDE.md).
     *
     * @return array<int, string>
     */
    protected static ?array $enabledCodes = null;

    protected static ?string $defaultCode = null;

    /** @return array<int, string> */
    public static function enabledCodes(): array
    {
        return static::$enabledCodes ??= static::query()
            ->where('is_enabled', true)
            ->orderBy('code')
            ->pluck('code')
            ->all();
    }

    /** The default locale's code, falling back to config when no row is flagged. */
    public static function defaultCode(): string
    {
        return static::$defaultCode ??= (string) (static::default()?->code ?? config('app.fallback_locale'));
    }

    /** Drop the memos — called by LanguageObserver on any write. */
    public static function forgetCodes(): void
    {
        static::$enabledCodes = null;
        static::$defaultCode = null;
    }
}
