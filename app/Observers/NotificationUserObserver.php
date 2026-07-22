<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

/**
 * An inbox row: a notification reaching one recipient. Logged as a pivot against
 * the parent notification (attached/detached), so the trail records who a
 * notification reached. Read-state changes (markRead) write nothing — pivot
 * mode's updated() is a no-op, which is exactly right here.
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
