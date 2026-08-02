<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class FormFieldObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'form-fields';
    }

    /**
     * `order` churns on every drag in the builder, and `content` is a rich-text
     * element's full HTML in every locale.
     *
     * @return array<int, string>
     */
    protected function ignored(): array
    {
        return ['order', 'content'];
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['form_id', 'key', 'type', 'label'];
    }

    /**
     * The machine key, not the label: the label is a locale map, and `key` is
     * what the export column, the webhook mapping and the stored answer all use.
     *
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->key];
    }
}
