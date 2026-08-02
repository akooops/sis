<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class FormWebhookObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'form-webhooks';
    }

    /**
     * `auth_config` is the bearer token / signing secret. It is encrypted at
     * rest, $hidden on the model and absent from the read DTO — and named here
     * too, because a diff would otherwise print the decrypted array into an
     * activity row. All four are needed; three is a leak.
     *
     * `last_delivered_at` moves on every submission and is not an admin action.
     *
     * @return array<int, string>
     */
    protected function ignored(): array
    {
        return ['auth_config', 'last_delivered_at'];
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['form_id', 'name', 'url'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
