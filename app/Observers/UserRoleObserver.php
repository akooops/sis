<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class UserRoleObserver extends BaseObserver
{
    protected function isPivot(): bool
    {
        return true;
    }

    /** A role being granted is something you look up on the user. */
    protected function logName(): string
    {
        return 'users';
    }

    protected function parent(Model $pivot): ?Model
    {
        return $pivot->user;
    }

    protected function related(Model $pivot): ?Model
    {
        return $pivot->role;
    }
}
