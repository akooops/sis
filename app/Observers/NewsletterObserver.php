<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class NewsletterObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'newsletters';
    }

    /**
     * `content` is the full HTML body, and updated() writes both sides of a diff —
     * logging it would put two copies of the email in every audit row.
     *
     * @return array<int, string>
     */
    protected function ignored(): array
    {
        return ['content'];
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        // Both pipelines: a deleted issue still records whether it had been public.
        return ['name', 'subject', 'status', 'publish_status', 'published_at'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
