<?php

namespace App\Data\Integration;

use App\Models\IntegrationType;
use Spatie\LaravelData\Data;

/**
 * Output DTO for a type card. Aggregates the type's integrations into the
 * glanceable status the card renders. Expects `$type->integrations` to be loaded.
 */
class IntegrationTypeData extends Data
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public ?string $icon,
        public int $integrations_count,
        public int $active_count,
        // active | disabled | not_configured
        public string $status,
    ) {}

    public static function fromModel(IntegrationType $type): self
    {
        $integrations = $type->integrations;
        $count = $integrations->count();
        $active = $integrations->where('is_enabled', true)->count();

        $status = match (true) {
            $count === 0 => 'not_configured',
            $active === 0 => 'disabled',
            default => 'active',
        };

        return new self(
            id: $type->id,
            code: $type->code,
            name: $type->name,
            icon: $type->icon,
            integrations_count: $count,
            active_count: $active,
            status: $status,
        );
    }
}
