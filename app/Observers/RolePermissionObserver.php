<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class RolePermissionObserver extends BaseObserver
{
    protected function isPivot(): bool
    {
        return true;
    }

    protected function logName(): string
    {
        return 'roles';
    }

    protected function parent(Model $pivot): ?Model
    {
        return $pivot->role;
    }

    protected function related(Model $pivot): ?Model
    {
        return $pivot->permission;
    }
}
