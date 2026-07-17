<?php

namespace Database\Seeders;

use App\Models\IntegrationType;
use Illuminate\Database\Seeder;

/**
 * Mirrors config('integrations.types') into the integration_types table. Adding a
 * new capability is: add a row to the config array (and a driver), then reseed.
 */
class IntegrationTypesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('integrations.types', []) as $code => $type) {
            IntegrationType::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $type['name'],
                    'icon' => $type['icon'] ?? null,
                    'sort' => $type['sort'] ?? 0,
                ],
            );
        }
    }
}
