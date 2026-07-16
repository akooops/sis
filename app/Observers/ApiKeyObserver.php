<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class ApiKeyObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'api-keys';
    }

    /**
     * The hash is the key itself — logging it would put a working credential in
     * the audit trail. (neverLog() already covers it; named here too because
     * this is the model where it matters.)
     *
     * @return array<int, string>
     */
    protected function ignored(): array
    {
        return ['hash'];
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['name', 'prefix', 'allowed_ips', 'expires_at', 'last_used_at'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
