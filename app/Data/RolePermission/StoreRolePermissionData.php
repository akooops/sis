<?php

namespace App\Data\RolePermission;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class StoreRolePermissionData extends Data
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        public array $permissions,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,id'],
        ];
    }
}
