<?php

namespace App\Data\Integration;

use App\Models\IntegrationDriver;
use Spatie\LaravelData\Data;

/**
 * Output DTO for a driver in the catalogue — drives the "choose a provider" step
 * and the schema-driven form. `schema` is the stored field list (same shape as
 * FieldData) mirrored from the driver class at seed time.
 */
class IntegrationDriverData extends Data
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public ?string $icon,
        /** @var array<int, array<string, mixed>> */
        public array $schema,
    ) {}

    public static function fromModel(IntegrationDriver $driver): self
    {
        return new self(
            id: $driver->id,
            code: $driver->code,
            name: $driver->name,
            icon: $driver->icon,
            schema: $driver->schema ?? [],
        );
    }
}
