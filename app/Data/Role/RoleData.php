<?php

namespace App\Data\Role;

use App\Models\Role;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/** `permissions` is Lazy — serialised only when eager-loaded. */
class RoleData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $code,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Role $role): self
    {
        return new self(
            id: $role->id,
            name: $role->name,
            code: $role->code,
            created_at: $role->created_at?->toIso8601String(),
            updated_at: $role->updated_at?->toIso8601String(),
        );
    }
}
