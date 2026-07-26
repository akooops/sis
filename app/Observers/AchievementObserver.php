<?php

namespace App\Observers;

use App\Models\Achievement;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class AchievementObserver extends BaseObserver
{
    /**
     * Free the thumbnail and every attached file back into the reusable pool
     * rather than destroying them — media outlives its owner.
     */
    public function deleting(Achievement $achievement): void
    {
        UploadService::freeModel($achievement);
    }

    protected function logName(): string
    {
        return 'achievements';
    }

    /**
     * `content` is a JSON map of every locale's full HTML — two copies of it per
     * save would make the activity log unreadable and unbounded.
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
        return ['name', 'slug', 'status', 'published_at', 'achieved_at'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
