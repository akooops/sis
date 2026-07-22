<?php

namespace Database\Seeders;

use App\Models\NotificationType;
use Illuminate\Database\Seeder;

/**
 * Mirrors config('notifications.types') into the notification_types table. Adding
 * a new type is: add a row to the config array, then reseed. No CRUD.
 */
class NotificationTypesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('notifications.types', []) as $code => $type) {
            NotificationType::updateOrCreate(
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
