<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

/** A target change is an activity on the newsletter — that is where groups are chosen. */
class NewsletterNewsletterGroupObserver extends BaseObserver
{
    protected function isPivot(): bool
    {
        return true;
    }

    protected function logName(): string
    {
        return 'newsletters';
    }

    protected function parent(Model $pivot): ?Model
    {
        return $pivot->newsletter;
    }

    protected function related(Model $pivot): ?Model
    {
        return $pivot->group;
    }
}
