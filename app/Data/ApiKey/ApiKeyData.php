<?php

namespace App\Data\ApiKey;

use App\Data\Permission\PermissionData;
use App\Models\ApiKey;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * Output DTO for an API key. Never exposes the hash/secret — the plaintext
 * token is only returned once, by the controller, on create/rotate.
 */
class ApiKeyData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $prefix,
        /** @var array<int, string>|null */
        public ?array $allowed_ips,
        public bool $is_active,
        public ?string $last_used_at,
        public ?string $last_used_ip,
        public ?string $expires_at,
        public ?string $revoked_at,
        public ?string $created_at,
        public ?string $updated_at,
        /** @var array<int, PermissionData> */
    ) {}

    public static function fromModel(ApiKey $apiKey): self
    {
        return new self(
            id: $apiKey->id,
            name: $apiKey->name,
            prefix: $apiKey->prefix,
            allowed_ips: $apiKey->allowed_ips,
            is_active: $apiKey->is_active,
            last_used_at: $apiKey->last_used_at?->toIso8601String(),
            last_used_ip: $apiKey->last_used_ip,
            expires_at: $apiKey->expires_at?->toIso8601String(),
            revoked_at: $apiKey->revoked_at?->toIso8601String(),
            created_at: $apiKey->created_at?->toIso8601String(),
            updated_at: $apiKey->updated_at?->toIso8601String(),
        );
    }
}
