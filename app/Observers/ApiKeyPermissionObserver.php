<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class ApiKeyPermissionObserver extends BaseObserver
{
    protected function isPivot(): bool
    {
        return true;
    }

    protected function logName(): string
    {
        return 'api-keys';
    }

    protected function parent(Model $pivot): ?Model
    {
        return $pivot->apiKey;
    }

    protected function related(Model $pivot): ?Model
    {
        return $pivot->permission;
    }
}
