<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class CategoryObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'categories';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['name', 'code', 'type'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
