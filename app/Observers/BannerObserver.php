<?php

namespace App\Observers;

use App\Models\Banner;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class BannerObserver extends BaseObserver
{
    /** Free the thumbnail and video back into the library, not off the disk. */
    public function deleting(Banner $banner): void
    {
        UploadService::freeModel($banner);
    }

    protected function logName(): string
    {
        return 'banners';
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
        return ['name', 'url'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
