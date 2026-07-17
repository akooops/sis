<?php

namespace App\Data\Integration;

use App\Models\IntegrationType;
use App\Services\Integrations\Registry;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class StoreIntegrationData extends Data
{
    public function __construct(
        public string $integration_type_id,
        public string $driver,
        public string $name,
        /** @var array<string, mixed> */
        public array $settings,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $registry = app(Registry::class);
        $driverCode = request()->input('driver');
        $typeCode = IntegrationType::find(request()->input('integration_type_id'))?->code;

        $rules = [
            'integration_type_id' => ['required', 'string', Rule::exists('integration_types', 'id')],
            'driver' => ['required', 'string', function (string $attribute, mixed $value, callable $fail) use ($registry, $typeCode) {
                if (! $registry->has($value)) {
                    $fail('The selected driver is invalid.');

                    return;
                }
                if ($typeCode !== null && $registry->driver($value)->type() !== $typeCode) {
                    $fail('The selected driver does not belong to this type.');
                }
            }],
            'name' => ['required', 'string', 'max:255'],
            'settings' => ['required', 'array'],
        ];

        if (is_string($driverCode) && $registry->has($driverCode)) {
            $rules = array_merge($rules, $registry->rulesFor($registry->driver($driverCode)->schema(), forUpdate: false));
        }

        return $rules;
    }
}
