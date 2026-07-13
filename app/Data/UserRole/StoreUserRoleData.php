<?php

namespace App\Data\UserRole;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class StoreUserRoleData extends Data
{
    /**
     * @param  array<int, string>  $roles
     */
    public function __construct(
        public array $roles,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'roles' => ['required', 'array'],
            'roles.*' => ['string', 'exists:roles,id'],
        ];
    }
}
