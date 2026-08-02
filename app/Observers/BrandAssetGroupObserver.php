<?php

namespace App\Observers;

use App\Models\BrandAssetGroup;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class BrandAssetGroupObserver extends BaseObserver
{
    /**
     * The group's assets go with it by DB cascade, which fires no model events — so
     * BrandAssetObserver::deleting never runs for them. Free their files here, while
     * the rows still exist: once the cascade lands, the media is attached to an owner
     * that is gone, which no longer frees (nothing owns it) and no longer deletes
     * (media.destroy refuses attached files).
     */
    public function deleting(BrandAssetGroup $group): void
    {
        foreach ($group->assets()->cursor() as $asset) {
            UploadService::freeModel($asset);
        }
    }

    protected function logName(): string
    {
        return 'brand-asset-groups';
    }

    /**
     * `order` is presentation nobody set by hand — the reorder endpoint writes it
     * with a builder update anyway, so a diff here would only ever be noise.
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
        return ['name', 'brand_id'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
