<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class NotificationObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'notifications';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['type', 'title'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->title];
    }
}
