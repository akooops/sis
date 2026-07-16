<?php

namespace App\Data\Role;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class StoreRoleData extends Data
{
    public function __construct(
        public string $name,
        public string $code,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')],
            'code' => ['required', 'string', 'max:255', Rule::unique('roles', 'code')],
        ];
    }
}
