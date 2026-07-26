<?php

namespace App\Observers;

use App\Models\Page;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class PageObserver extends BaseObserver
{
    /**
     * Free the thumbnail and every content image back into the reusable pool
     * rather than destroying the files — media outlives its owner, as with
     * UserObserver and LanguageObserver. model:prune sweeps them 30 days later if
     * nobody claims them, and re-saving another page that still links one re-owns
     * it via UploadService::sync().
     */
    public function deleting(Page $page): void
    {
        UploadService::freeModel($page);
    }

    protected function logName(): string
    {
        return 'pages';
    }

    /**
     * `content` is a JSON map of every locale's full HTML. BaseObserver::updated()
     * writes both the old and the new side of a diff, so logging it would put two
     * copies of the whole page body into activity_log.properties on every save —
     * unreadable in the drawer and unbounded on disk. Same call IntegrationObserver
     * makes about `config`.
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
        return ['name', 'slug', 'status', 'published_at', 'is_system'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
