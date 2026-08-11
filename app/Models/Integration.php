<?php

namespace App\Models;

use App\Contracts\Integrations\Driver;
use App\Services\Integrations\Registry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

/**
 * A configured instance of a driver (this SMTP account, that OpenAI key).
 *
 * Every field lives in `config`, an encrypted:array blob that never leaves the
 * server: $hidden, absent from the read DTO, and in the observer's ignored().
 */
class Integration extends Model
{
    use HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $hidden = ['config'];

    protected $casts = [
        'config' => 'encrypted:array',
        'is_enabled' => 'bool',
    ];

    /**
     * Per-request memo: the mail bridge resolves the active email integration on
     * every request in AppServiceProvider::boot().
     *
     * @var array<string, self|null>
     */
    protected static array $activeMemo = [];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function type(): BelongsTo
    {
        return $this->belongsTo(IntegrationType::class, 'integration_type_id');
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeOfType(Builder $query, string $typeCode): Builder
    {
        return $query->whereHas('type', fn (Builder $q) => $q->where('code', $typeCode));
    }

    /** The driver class that backs this integration, resolved from the registry. */
    public function resolveDriver(): Driver
    {
        return app(Registry::class)->driver($this->driver);
    }

    /**
     * What the app sends through for a type.
     *
     * The `integrations.{type}` SETTING when an admin has pinned one, and the
     * oldest enabled integration otherwise. The fallback is what makes a single
     * configured integration work with nothing chosen; the setting is what
     * decides between two, which the fallback picks arbitrarily.
     *
     * Only the types with a setting declared in config/settings.php can be
     * pinned — today `analytics` and `ai`. Everything else keeps the fallback.
     *
     * Email/Sms/Ai::for($id) still pins one explicitly and bypasses both.
     */
    public static function activeFor(string $typeCode): ?self
    {
        if (array_key_exists($typeCode, static::$activeMemo)) {
            return static::$activeMemo[$typeCode];
        }

        return static::$activeMemo[$typeCode] = static::pinnedFor($typeCode)
            ?? static::query()->ofType($typeCode)->enabled()->oldest()->first();
    }

    /**
     * The integration an admin pinned for this type, if any.
     *
     * Re-checks the type and the enabled flag rather than trusting the stored
     * id: a setting written before an integration was disabled, retyped or
     * deleted must stop resolving, not keep sending through a dead account.
     *
     * Guarded, because this runs from AppServiceProvider::boot() — before
     * `settings` necessarily exists on a fresh install or mid-migration.
     */
    protected static function pinnedFor(string $typeCode): ?self
    {
        try {
            $id = Setting::query()
                ->where('group', 'integrations')
                ->where('key', $typeCode)
                ->value('value');
        } catch (Throwable) {
            return null;
        }

        // The column is json-cast, so a bare id round-trips as a quoted string.
        $id = is_string($id) ? trim($id, '"') : null;

        if ($id === null || $id === '') {
            return null;
        }

        return static::query()->ofType($typeCode)->enabled()->whereKey($id)->first();
    }

    /** Drop the per-request active memo (called by the observer on any write). */
    public static function forgetActive(): void
    {
        static::$activeMemo = [];
    }
}
