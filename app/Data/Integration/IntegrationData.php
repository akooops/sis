<?php

namespace App\Data\Integration;

use App\Models\Integration;
use App\Services\Integrations\Registry;
use Spatie\LaravelData\Data;

/**
 * Never exposes a secret value: config is split into plain values for the form
 * plus a secrets_set map, so the UI can render "••••" without the value.
 */
class IntegrationData extends Data
{
    public function __construct(
        public string $id,
        public string $integration_type_id,
        public string $driver,
        public string $name,
        /** @var array<string, mixed> */
        public array $config,
        /** @var array<string, bool> */
        public array $secrets_set,
        public bool $is_enabled,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Integration $integration): self
    {
        $registry = app(Registry::class);
        $schema = $registry->has($integration->driver) ? $registry->driver($integration->driver)->schema() : [];
        [$config, $secretsSet] = $registry->splitForRead($schema, $integration->config ?? []);

        return new self(
            id: $integration->id,
            integration_type_id: $integration->integration_type_id,
            driver: $integration->driver,
            name: $integration->name,
            config: $config,
            secrets_set: $secretsSet,
            is_enabled: $integration->is_enabled,
            created_at: $integration->created_at?->toIso8601String(),
            updated_at: $integration->updated_at?->toIso8601String(),
        );
    }
}
