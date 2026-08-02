<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

/**
 * Routing a form to a group is an activity on the form. Edited from both ends —
 * the form's page and the group's — and both write the same row, so both show up
 * here identically.
 */
class FormNotificationGroupObserver extends BaseObserver
{
    protected function isPivot(): bool
    {
        return true;
    }

    protected function logName(): string
    {
        return 'forms';
    }

    protected function parent(Model $pivot): ?Model
    {
        return $pivot->form;
    }

    protected function related(Model $pivot): ?Model
    {
        return $pivot->group;
    }
}
