<?php

namespace App\Data\Integration;

use App\Models\IntegrationDriver;
use Spatie\LaravelData\Data;

/** `schema` is the driver's field list, mirrored into the DB at seed time. */
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
