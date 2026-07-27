<?php

namespace App\Observers;

use App\Models\Program;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class ProgramObserver extends BaseObserver
{
    /** Free the media back to the pool rather than destroying it: it outlives its owner. */
    public function deleting(Program $program): void
    {
        UploadService::freeModel($program);
    }

    protected function logName(): string
    {
        return 'programs';
    }

    /**
     * `content` is every locale's full HTML, and updated() writes both sides of a
     * diff. `order` is written by a builder update, so only a create would record
     * it — noise on a field nobody set.
     *
     * @return array<int, string>
     */
    protected function ignored(): array
    {
        return ['order', 'content'];
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['name', 'slug'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
