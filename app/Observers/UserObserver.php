<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Notifications\NotificationService;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class UserObserver extends BaseObserver
{
    /**
     * Audit the create (parent), then tell the approvers a signup is waiting.
     * The parameter stays Model — PHP forbids narrowing BaseObserver::created().
     */
    public function created(Model $model): void
    {
        parent::created($model);

        if ($model instanceof User && ! $model->canLogin()) {
            $name = trim("{$model->firstname} {$model->lastname}") ?: $model->username;

            NotificationService::send('user.pending_approval', [
                'title' => 'User pending approval',
                'body' => "{$name} ({$model->email}) is awaiting approval.",
                'route_name' => 'web.admin.users.index',
                'route_params' => [
                    'filter[id]' => $model->id,
                    'filter[status]' => $model->status->getValue(),
                ],
            ]);
        }
    }

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
