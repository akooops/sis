<?php

namespace App\Data\ApiKeyPermission;

use App\Data\ApiKey\ApiKeyData;
use App\Data\Permission\PermissionData;
use App\Models\ApiKeyPermission;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

class ApiKeyPermissionData extends Data
{
    public function __construct(
        public string $id,
        public string $api_key_id,
        public string $permission_id,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|ApiKeyData $apiKey,
        public Lazy|PermissionData $permission,
    ) {}

    public static function fromModel(ApiKeyPermission $apiKeyPermission): self
    {
        return new self(
            id: $apiKeyPermission->id,
            api_key_id: $apiKeyPermission->api_key_id,
            permission_id: $apiKeyPermission->permission_id,
            created_at: $apiKeyPermission->created_at?->toIso8601String(),
            updated_at: $apiKeyPermission->updated_at?->toIso8601String(),
            apiKey: Lazy::whenLoaded('apiKey', $apiKeyPermission, fn () => ApiKeyData::from($apiKeyPermission->apiKey)),
            permission: Lazy::whenLoaded('permission', $apiKeyPermission, fn () => PermissionData::from($apiKeyPermission->permission)),
        );
    }
}
