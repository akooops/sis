<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class PermissionObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'permissions';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['code', 'name', 'supports_web', 'supports_api'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name ?? $model->code];
    }
}
