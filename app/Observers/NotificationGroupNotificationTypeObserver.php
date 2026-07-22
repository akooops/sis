<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

/**
 * Adding/removing a notification type on a group is an activity on the group
 * (that is where its types are managed).
 */
class NotificationGroupNotificationTypeObserver extends BaseObserver
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
