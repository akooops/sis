<?php

namespace App\Observers;

use App\Models\Achievement;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class AchievementObserver extends BaseObserver
{
    /** Free the media back to the pool rather than destroying it: it outlives its owner. */
    public function deleting(Achievement $achievement): void
    {
        UploadService::freeModel($achievement);
    }

    protected function logName(): string
    {
        return 'achievements';
    }

    /**
     * `content` is every locale's full HTML, and updated() writes both sides of a
     * diff — logging it would put two copies of the body in every audit row.
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
