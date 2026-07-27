<?php

namespace App\Data\UserRole;

use App\Data\Role\RoleData;
use App\Data\User\UserData;
use App\Models\UserRole;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

class UserRoleData extends Data
{
    public function __construct(
        public string $id,
        public string $user_id,
        public string $role_id,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|UserData $user,
        public Lazy|RoleData $role,
    ) {}

    public static function fromModel(UserRole $userRole): self
    {
        return new self(
            id: $userRole->id,
            user_id: $userRole->user_id,
            role_id: $userRole->role_id,
            created_at: $userRole->created_at?->toIso8601String(),
            updated_at: $userRole->updated_at?->toIso8601String(),
            user: Lazy::whenLoaded('user', $userRole, fn () => UserData::from($userRole->user)),
            role: Lazy::whenLoaded('role', $userRole, fn () => RoleData::from($userRole->role)),
        );
    }
}
