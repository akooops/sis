<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Uploads\UploadService;

class UserObserver
{
    /**
     * On permanent (force) deletion, free the user's media back into the
     * reusable pool instead of destroying the files. Soft deletes keep the
     * media attached so a restored user still has its avatar.
     */
    public function deleting(User $user): void
    {
        if ($user->isForceDeleting()) {
            UploadService::freeModel($user);
        }
    }
}
