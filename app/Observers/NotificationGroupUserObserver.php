<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

/**
 * Adding/removing a member is an activity on the group. Users have no `name`
 * column, so the label falls back to username/email.
 */
class NotificationGroupUserObserver extends BaseObserver
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
        return $pivot->user;
    }

    protected function label(?Model $related): ?string
    {
        return $related?->getAttribute('username') ?? $related?->getAttribute('email');
    }
}
