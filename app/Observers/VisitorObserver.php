<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

/**
 * A visitor is a real person, so changes to their contact details are audited.
 *
 * Note the projector fills a household on EVERY booking, so a family that books a
 * second visit with a corrected phone number produces an updated() row here. That
 * is the point: it is the only record that the number on file changed and when.
 */
class VisitorObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'visitors';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['first_name', 'last_name', 'email', 'phone'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->full_name];
    }
}
