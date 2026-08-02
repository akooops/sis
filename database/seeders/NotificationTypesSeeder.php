<?php

namespace Database\Seeders;

use App\Models\NotificationType;
use Illuminate\Database\Seeder;

/**
 * Mirrors config('notifications.types') into the notification_types table.
 * Adding a new type is: add a row to the config array, then reseed. No CRUD.
 *
 * Every seeded type is subscribable — a group ticks it and gets it — and every
 * notification carries one, including the ones a form also routes to its own
 * attached groups.
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
