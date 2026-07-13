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
        public bool|Optional $is_default,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $role = request()->route('role');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role)->whereNull('deleted_at')],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
