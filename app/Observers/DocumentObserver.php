<?php

namespace App\Observers;

use App\Models\Document;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class DocumentObserver extends BaseObserver
{
    /** Free the media back to the pool rather than destroying it: it outlives its owner. */
    public function deleting(Document $document): void
    {
        UploadService::freeModel($document);
    }

    protected function logName(): string
    {
        return 'documents';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['name'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
