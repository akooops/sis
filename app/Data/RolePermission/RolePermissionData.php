<?php

namespace App\Data\RolePermission;

use App\Data\Permission\PermissionData;
use App\Data\Role\RoleData;
use App\Models\RolePermission;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/** Both sides are Lazy — each serialised only when eager-loaded. */
class RolePermissionData extends Data
{
    public function __construct(
        public string $id,
        public string $role_id,
        public string $permission_id,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|RoleData $role,
        public Lazy|PermissionData $permission,
    ) {}

    public static function fromModel(RolePermission $rolePermission): self
    {
        return new self(
            id: $rolePermission->id,
            role_id: $rolePermission->role_id,
            permission_id: $rolePermission->permission_id,
            created_at: $rolePermission->created_at?->toIso8601String(),
            updated_at: $rolePermission->updated_at?->toIso8601String(),
            role: Lazy::whenLoaded('role', $rolePermission, fn () => RoleData::from($rolePermission->role)),
            permission: Lazy::whenLoaded('permission', $rolePermission, fn () => PermissionData::from($rolePermission->permission)),
        );
    }
}
