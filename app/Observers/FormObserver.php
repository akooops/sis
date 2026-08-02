<?php

namespace App\Observers;

use App\Models\Form;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class FormObserver extends BaseObserver
{
    /** Free the media back to the pool rather than destroying it: it outlives its owner. */
    public function deleting(Form $form): void
    {
        UploadService::freeModel($form);
    }

    protected function logName(): string
    {
        return 'forms';
    }

    /**
     * `content` and `confirmation_message` are every locale's full HTML, and
     * updated() writes both sides of a diff. `submissions_count` moves on every
     * accepted submission, which would bury the changes an admin actually made.
     *
     * @return array<int, string>
     */
    protected function ignored(): array
    {
        return ['content', 'confirmation_message', 'submissions_count'];
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['name', 'slug', 'status', 'is_system', 'published_at'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
