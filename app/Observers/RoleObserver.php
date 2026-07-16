<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class RoleObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'roles';
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
