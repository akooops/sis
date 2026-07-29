<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class NewsletterGroupSubscriberObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'newsletter-group-subscribers';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['email', 'newsletter_group_id'];
    }

    /**
     * The name is optional, so the address is what identifies the row.
     *
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name ?? $model->email];
    }
}
