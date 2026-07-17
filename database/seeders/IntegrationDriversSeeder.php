<?php

namespace Database\Seeders;

use App\Models\IntegrationDriver;
use App\Models\IntegrationType;
use App\Services\Integrations\Registry;
use Illuminate\Database\Seeder;

/**
 * Mirrors the drivers registered in config('integrations.drivers') into the
 * integration_drivers catalogue table, carrying each driver's field schema so the
 * admin UI can list drivers and build forms from the DB. Run after
 * IntegrationTypesSeeder (needs the type ids).
 */
class IntegrationDriversSeeder extends Seeder
{
    public function run(): void
    {
        $types = IntegrationType::pluck('id', 'code');

        foreach (app(Registry::class)->all() as $driver) {
            $typeId = $types[$driver->type()] ?? null;

            if ($typeId === null) {
                continue;
            }

            IntegrationDriver::updateOrCreate(
                ['code' => $driver->code()],
                [
                    'name' => $driver->label(),
                    'icon' => $driver->icon(),
                    'schema' => array_map(fn ($field) => $field->toArray(), $driver->schema()),
                    'integration_type_id' => $typeId,
                ],
            );
        }
    }
}
