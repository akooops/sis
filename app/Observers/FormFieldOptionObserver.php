<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class FormFieldOptionObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'form-fields';
    }

    /**
     * @return array<int, string>
     */
    protected function ignored(): array
    {
        return ['order'];
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['form_field_id', 'value'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->value];
    }
}
