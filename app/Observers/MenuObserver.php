<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class MenuObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'menus';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['name', 'code', 'is_system'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
