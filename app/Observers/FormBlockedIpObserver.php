<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

/**
 * Not a pivot: there is no second model to point at, only a literal address.
 * Logged as its own create/delete against the blocked-ip row so the value is
 * visible in the diff.
 */
class FormBlockedIpObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'forms';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['form_id', 'value'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->value];
    }
}
