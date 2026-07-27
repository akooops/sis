<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class StreamObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'streams';
    }

    /**
     * `order` is presentation nobody set by hand, and `content` is every locale's
     * full HTML — updated() writes both sides of a diff, so logging it would put
     * two copies of the body in every audit row.
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
        return ['name', 'slug', 'program_id'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
