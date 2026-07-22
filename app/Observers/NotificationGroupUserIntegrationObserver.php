<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

/**
 * Choosing/removing a delivery integration for a member is an activity on the
 * group (that is where memberships and their integrations are managed).
 */
class NotificationGroupUserIntegrationObserver extends BaseObserver
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
        return $pivot->groupUser?->group;
    }

    protected function related(Model $pivot): ?Model
    {
        return $pivot->integration;
    }
}
