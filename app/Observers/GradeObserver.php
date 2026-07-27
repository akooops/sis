<?php

namespace App\Observers;

use App\Models\Grade;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class GradeObserver extends BaseObserver
{
    /** Free the guidelines back into the library rather than destroying the files. */
    public function deleting(Grade $grade): void
    {
        UploadService::freeModel($grade);
    }

    protected function logName(): string
    {
        return 'grades';
    }

    /**
     * Reordering is a builder update so it never reaches an observer, but a create
     * would still record order 0/1/2 — noise on a field nobody set.
     *
     * @return array<int, string>
     */
    protected function ignored(): array
    {
        return ['order'];
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['name', 'program_id'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
