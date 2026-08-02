<?php

namespace App\Observers;

use App\Models\BrandAsset;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class BrandAssetObserver extends BaseObserver
{
    /** Free the media back to the pool rather than destroying it: it outlives its owner. */
    public function deleting(BrandAsset $asset): void
    {
        UploadService::freeModel($asset);
    }

    protected function logName(): string
    {
        return 'brand-assets';
    }

    /**
     * `order` is presentation nobody set by hand — a drag would otherwise write an
     * audit row per asset.
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
        return ['name', 'brand_asset_group_id'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
