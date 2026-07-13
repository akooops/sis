<?php

namespace App\Data\User;

use App\Data\Role\RoleData;
use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * Output DTO for a user. `roles` is Lazy — included only when eager-loaded
 * (e.g. $user->load('roles')). Note there is no `password`: output DTOs simply
 * omit write-only fields.
 */
class UserData extends Data
{
    public function __construct(
        public string $id,
        public string $firstname,
        public string $lastname,
        public string $username,
        public string $email,
        public ?string $phone,
        public ?string $avatar_url,
        public ?string $verified_at,
        public ?string $created_at,
        public ?string $updated_at,
        public ?string $deleted_at,
        /** @var array<int, RoleData> */
        public Lazy|array $roles,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            firstname: $user->firstname,
            lastname: $user->lastname,
            username: $user->username,
            email: $user->email,
            phone: $user->phone,
            avatar_url: $user->avatar_url,
            verified_at: $user->verified_at?->toIso8601String(),
            created_at: $user->created_at?->toIso8601String(),
            updated_at: $user->updated_at?->toIso8601String(),
            deleted_at: $user->deleted_at?->toIso8601String(),
            roles: Lazy::whenLoaded(
                'roles',
                $user,
                fn () => RoleData::collect($user->roles->all()),
            ),
        );
    }
}
