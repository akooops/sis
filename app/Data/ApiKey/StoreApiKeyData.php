<?php

namespace App\Data\ApiKey;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class StoreApiKeyData extends Data
{
    public function __construct(
        public string $name,
        /** @var array<int, string>|null */
        public array|Optional|null $allowed_ips,
        public string|Optional|null $expires_at,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('api_keys', 'name')->whereNull('deleted_at')],
            'allowed_ips' => ['sometimes', 'nullable', 'array'],
            'allowed_ips.*' => ['ip'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
