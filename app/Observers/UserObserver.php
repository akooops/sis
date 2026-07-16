<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class UserObserver extends BaseObserver
{
    /**
     * Free the user's media back into the reusable pool rather than destroying
     * the files — media outlives the user it was attached to.
     */
    public function deleting(User $user): void
    {
        UploadService::freeModel($user);
    }

    protected function logName(): string
    {
        return 'users';
    }

    /**
     * @return array<int, string>
     */
    protected function ignored(): array
    {
        return ['azure_ad_id'];
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['firstname', 'lastname', 'username', 'email', 'phone'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => trim("{$model->firstname} {$model->lastname}") ?: $model->username];
    }
}
