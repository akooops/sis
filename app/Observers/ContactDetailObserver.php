<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class ContactDetailObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'contact-details';
    }

    /**
     * Reordering is a builder update so it never reaches an observer, but a create
     * would still record order 0/1/2 — noise on a field nobody set.
     *
     * @return array<int, string>
     */
    protected function ignored(): array
    {
        return ['order'];
    }

    /**
     * The type says what was lost and the value says which one it was — a deleted
     * number is otherwise unrecoverable from the log.
     *
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['type', 'name', 'value'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
