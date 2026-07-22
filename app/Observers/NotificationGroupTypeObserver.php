<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

/**
 * Attaching/detaching a notification type to a group is an activity on the group.
 */
class NotificationGroupTypeObserver extends BaseObserver
{
    protected function isPivot(): bool
    {
        return true;
    }

    protected function logName(): string
    {
        return 'notification-groups';
    }

    protected function parent(Model $pivot): ?Model
    {
        return $pivot->group;
    }

    protected function related(Model $pivot): ?Model
    {
        return $pivot->type;
    }
}
