<?php

namespace App\Models;

use App\Contracts\Integrations\IntegrationDriver;
use App\Data\Integration\TestResultData;
use App\Services\Integrations\IntegrationRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * A configured instance of a driver (this SMTP account, that 4jawaly sender).
 *
 * Secrets live in `credentials`, an encrypted:array blob that never leaves the
 * server: it is in $hidden, absent from the read DTO, and named in the observer's
 * neverLog() so it can't reach the audit trail.
 */
class Provider extends Model
{
    use HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $hidden = ['credentials'];

    protected $casts = [
        'config' => 'array',
        'credentials' => 'encrypted:array',
        'is_enabled' => 'bool',
        'is_default' => 'bool',
        'last_test_ok' => 'bool',
        'last_tested_at' => 'datetime',
    ];

    /**
     * Per-request memo for activeFor(): the mail bridge resolves the active email
     * provider on every request in AppServiceProvider::boot(), so a repeat lookup
     * inside one request should not hit the database twice.
     *
     * @var array<string, self|null>
     */
    protected static array $activeMemo = [];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function type(): BelongsTo
    {
        return $this->belongsTo(ProviderType::class, 'provider_type_code', 'code');
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

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('provider_type_code', $type);
    }

    /** The driver class that backs this provider, resolved from the registry. */
    public function driver(): IntegrationDriver
    {
        return app(IntegrationRegistry::class)->driver($this->driver);
    }

    /**
     * The provider used when the app sends through a type: the enabled default,
     * or — if none is marked default — the newest enabled one. Null if the type
     * has no usable provider.
     */
    public static function activeFor(string $type): ?self
    {
        if (array_key_exists($type, static::$activeMemo)) {
            return static::$activeMemo[$type];
        }

        return static::$activeMemo[$type] = static::query()
            ->ofType($type)
            ->enabled()
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->first();
    }

    /** Drop the per-request active memo (called by the observer on any write). */
    public static function forgetActive(): void
    {
        static::$activeMemo = [];
    }

    /**
     * Make this the single default for its type. Only one row per type may be
     * default, so clearing the siblings and setting self is one transaction.
     */
    public function makeDefault(): void
    {
        DB::transaction(function () {
            static::query()
                ->ofType($this->provider_type_code)
                ->whereKeyNot($this->getKey())
                ->update(['is_default' => false]);

            $this->forceFill(['is_default' => true, 'is_enabled' => true])->save();
        });
    }

    /** Record the outcome of a connection test for the status dot. */
    public function recordTest(TestResultData $result): void
    {
        $this->forceFill([
            'last_tested_at' => now(),
            'last_test_ok' => $result->ok,
            'last_test_error' => $result->ok ? null : $result->message,
        ])->save();
    }
}
