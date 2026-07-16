<?php

namespace App\Data\Role;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class UpdateRoleData extends Data
{
    public function __construct(
        public string|Optional $name,
        public string|Optional $code,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $role = request()->route('role');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role)],
            'code' => ['sometimes', 'string', 'max:255', Rule::unique('roles', 'code')->ignore($role)],
        ];
    }
}
