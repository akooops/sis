<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

/**
 * Attaching a news item to a venue is an activity ON THE VENUE — that is what
 * pivot mode means, and it is why the log name is the parent's module rather than
 * the pivot's own.
 *
 * updated() is a no-op in pivot mode, because a pivot carries only its two keys:
 * changing a side is a detach plus an attach, and both are recorded.
 */
class FacilityArticleObserver extends BaseObserver
{
    protected function isPivot(): bool
    {
        return true;
    }

    protected function logName(): string
    {
        return 'facilities';
    }

    protected function parent(Model $pivot): ?Model
    {
        return $pivot->facility;
    }

    protected function related(Model $pivot): ?Model
    {
        return $pivot->article;
    }
}
