<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

/**
 * An inbox row. Logged as a pivot against the notification, so the trail records
 * who it reached. markRead writes nothing — pivot mode's updated() is a no-op.
 */
class NotificationUserObserver extends BaseObserver
{
    protected function isPivot(): bool
    {
        return true;
    }

    protected function logName(): string
    {
        return 'notifications';
    }

    protected function parent(Model $pivot): ?Model
    {
        return $pivot->notification;
    }

    protected function related(Model $pivot): ?Model
    {
        return $pivot->user;
    }
}
