<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class FormPageObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'form-pages';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['form_id', 'name', 'order'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
