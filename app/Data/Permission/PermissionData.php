<?php

namespace App\Data\Permission;

use App\Models\Permission;
use Spatie\LaravelData\Data;

class PermissionData extends Data
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public bool $supports_web,
        public bool $supports_api,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Permission $permission): self
    {
        return new self(
            id: $permission->id,
            code: $permission->code,
            name: $permission->name,
            supports_web: $permission->supports_web,
            supports_api: $permission->supports_api,
            created_at: $permission->created_at?->toIso8601String(),
            updated_at: $permission->updated_at?->toIso8601String(),
        );
    }
}
