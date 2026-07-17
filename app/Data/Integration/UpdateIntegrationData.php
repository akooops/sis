<?php

namespace App\Data\Integration;

use App\Services\Integrations\Registry;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class UpdateIntegrationData extends Data
{
    public function __construct(
        public string|Optional $name,
        /** @var array<string, mixed> */
        public array|Optional $settings,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $registry = app(Registry::class);
        $integration = request()->route('integration');

        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'settings' => ['sometimes', 'array'],
        ];

        // The driver is fixed on edit; validate submitted fields against it.
        if ($integration && $registry->has($integration->driver)) {
            $rules = array_merge($rules, $registry->rulesFor($registry->driver($integration->driver)->schema(), forUpdate: true));
        }

        return $rules;
    }
}
