<?php

namespace App\Data\Menu;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * No `is_system`: only the seeder sets it. A system menu's code is frozen — the
 * controller rejects the change, since this DTO cannot see which menu it is for.
 */
class UpdateMenuData extends Data
{
    public function __construct(
        public string $name,
        public string $code,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('menus', 'code')->ignore(request()->route('menu')),
            ],
        ];
    }
}
