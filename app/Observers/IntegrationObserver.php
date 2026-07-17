<?php

namespace App\Observers;

use App\Models\Integration;
use Illuminate\Database\Eloquent\Model;

class IntegrationObserver extends BaseObserver
{
    protected function logName(): string
    {
        return 'integrations';
    }

    /**
     * `config` is the encrypted settings blob (secrets included) — never audit it.
     *
     * @return array<int, string>
     */
    protected function ignored(): array
    {
        return ['config'];
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['integration_type_id', 'driver', 'name'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }

    /** Any write can change the active integration — drop the resolver's memo. */
    public function saved(Model $model): void
    {
        Integration::forgetActive();
    }

    public function deleted(Model $model): void
    {
        Integration::forgetActive();

        parent::deleted($model);
    }
}
