<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class MediaObserver extends BaseObserver
{
    /**
     * Media churns on columns nobody reads: the scan job rewrites disk/state, and
     * attach/detach rewrite the owner. The moments that matter are logged with
     * their own events by UploadService and ScanUpload.
     */
    public function updated(Model $model): void
    {
        //
    }

    protected function logName(): string
    {
        return 'media';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['name', 'file_name', 'mime_type', 'size', 'collection_name'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
