<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

/**
 * Nothing is ignored, deliberately. `value` is the only column an admin can
 * write, so its diff IS the trail this module exists for — the usual "drop the
 * noisy column" instinct would delete the whole point. The metadata columns only
 * move when a deploy reseeds them from config, and knowing that a setting's type
 * or options changed under an admin is worth its row too.
 *
 * No loggedOnDelete(): settings are seeded and there is no destroy route.
 */
class SettingObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'settings';
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
