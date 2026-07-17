<?php

namespace App\Models;

use App\Contracts\Integrations\Driver;
use App\Services\Integrations\Registry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A configured instance of a driver (this SMTP account, that OpenAI key).
 *
 * Every field lives in `config`, an encrypted:array blob that never leaves the
 * server: it is $hidden, absent from the read DTO (only non-secret values +
 * secrets_set booleans are exposed), and named in the observer's ignored() so it
 * can't reach the audit trail.
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
     * Per-request memo for activeFor(): the mail bridge resolves the active email
     * integration on every request in AppServiceProvider::boot().
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
     * The integration the app sends through for a type. Interim rule until a
     * settings table governs selection: the oldest enabled one of that type.
     * Explicit Email/Sms/Ai::for($id) pins a specific one instead.
     */
    public static function activeFor(string $typeCode): ?self
    {
        if (array_key_exists($typeCode, static::$activeMemo)) {
            return static::$activeMemo[$typeCode];
        }

        return static::$activeMemo[$typeCode] = static::query()
            ->ofType($typeCode)
            ->enabled()
            ->oldest()
            ->first();
    }

    /** Drop the per-request active memo (called by the observer on any write). */
    public static function forgetActive(): void
    {
        static::$activeMemo = [];
    }
}
