<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class NotificationGroupObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'notification-groups';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['name', 'code'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
