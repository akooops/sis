<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    /** Separates the public prefix from the secret in a plaintext token. */
    public const TOKEN_SEPARATOR = '-';

    // Attributes
    protected $guarded = ['id'];

    protected $hidden = ['hash'];

    protected $appends = ['is_active'];

    protected $casts = [
        'allowed_ips' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    // Relationships
    public function apiKeyPermissions(): HasMany
    {
        return $this->hasMany(ApiKeyPermission::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'api_key_permissions')
            ->using(ApiKeyPermission::class)
            ->withTimestamps();
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->isUsable();
    }

    // Lifecycle
    /**
     * Create a new key and return [model, plaintext token]. The token is only
     * available here — the database stores just its hash.
     *
     * @return array{0: self, 1: string}
     */
    public static function issue(array $attributes): array
    {
        $prefix = Str::random(12);
        $secret = Str::random(40);

        $apiKey = static::create([
            ...$attributes,
            'prefix' => $prefix,
            'hash' => hash('sha256', $secret),
        ]);

        return [$apiKey, $prefix.self::TOKEN_SEPARATOR.$secret];
    }

    /**
     * Issue a fresh secret (invalidating the old token) and re-activate the key.
     * Returns the new plaintext token.
     */
    public function rotate(): string
    {
        $secret = Str::random(40);

        $this->forceFill([
            'hash' => hash('sha256', $secret),
            'revoked_at' => null,
        ])->save();

        return $this->prefix.self::TOKEN_SEPARATOR.$secret;
    }

    public function revoke(): void
    {
        $this->forceFill(['revoked_at' => now()])->save();
    }

    // Authentication
    /**
     * Resolve and authenticate a plaintext token, enforcing usability and the
     * IP allow-list, and stamping last-used. Returns null when invalid.
     */
    public static function validate(string $token, ?string $ip = null): ?self
    {
        if (! str_contains($token, self::TOKEN_SEPARATOR)) {
            return null;
        }

        [$prefix, $secret] = explode(self::TOKEN_SEPARATOR, $token, 2);

        $apiKey = static::query()->where('prefix', $prefix)->first();

        if (! $apiKey
            || ! $apiKey->isUsable()
            || ! hash_equals($apiKey->hash, hash('sha256', $secret))
            || ! $apiKey->allowsIp($ip)) {
            return null;
        }

        $apiKey->forceFill([
            'last_used_at' => now(),
            'last_used_ip' => $ip,
        ])->save();

        return $apiKey;
    }

    public function isUsable(): bool
    {
        return $this->revoked_at === null
            && ! ($this->expires_at && $this->expires_at->isPast());
    }

    public function allowsIp(?string $ip): bool
    {
        $allowed = $this->allowed_ips ?? [];

        return $allowed === [] || ($ip !== null && in_array($ip, $allowed, true));
    }

    // Permissions
    /**
     * Set the key's permissions to exactly the given ids.
     *
     * @param  array<int, string>  $permissionIds
     */
    public function syncPermissions(array $permissionIds): void
    {
        $this->apiKeyPermissions()
            ->whereNotIn('permission_id', $permissionIds)
            ->delete();

        foreach ($permissionIds as $permissionId) {
            $this->apiKeyPermissions()->firstOrCreate(['permission_id' => $permissionId]);
        }
    }

    // An API key acts on the api channel, so only api-enabled permissions count.
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()
            ->where('code', $permission)
            ->where('supports_api', true)
            ->exists();
    }

    public function hasPermissions(array $permissions): bool
    {
        $permissions = array_unique($permissions);

        if ($permissions === []) {
            return true;
        }

        return $this->permissions()
            ->whereIn('code', $permissions)
            ->where('supports_api', true)
            ->distinct()
            ->count('permissions.code') === count($permissions);
    }
}
