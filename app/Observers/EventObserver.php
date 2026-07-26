<?php

namespace App\Observers;

use App\Models\Event;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class EventObserver extends BaseObserver
{
    /**
     * Free the thumbnail and every attached file back into the reusable pool
     * rather than destroying them — media outlives its owner, as with
     * UserObserver and LanguageObserver. model:prune sweeps them 30 days later
     * if nobody claims them.
     */
    public function deleting(Event $event): void
    {
        UploadService::freeModel($event);
    }

    protected function logName(): string
    {
        return 'events';
    }

    /**
     * `content` is a JSON map of every locale's full HTML. BaseObserver::updated()
     * writes both sides of a diff, so logging it would put two copies of the whole
     * body into activity_log.properties on every save — unreadable in the drawer
     * and unbounded on disk. Same call IntegrationObserver makes about `config`.
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
        return ['name', 'slug', 'status', 'published_at', 'start_at', 'end_at'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
