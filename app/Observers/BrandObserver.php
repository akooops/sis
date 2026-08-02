<?php

namespace App\Observers;

use App\Models\Brand;
use App\Models\BrandAsset;
use App\Models\BrandAssetGroup;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class BrandObserver extends BaseObserver
{
    /**
     * Free the media back to the pool rather than destroying it: it outlives its owner.
     *
     * The brand's groups and their assets go by DB cascade, which fires no model
     * events, so neither child observer runs — the asset files are freed here too.
     * Left alone they would be attached to an owner that is gone, which no longer
     * frees (nothing owns it) and no longer deletes (media.destroy refuses attached
     * files). Two levels down, so the group observer cannot cover it either.
     */
    public function deleting(Brand $brand): void
    {
        UploadService::freeModel($brand);

        $assets = BrandAsset::query()->whereIn(
            'brand_asset_group_id',
            BrandAssetGroup::query()->where('brand_id', $brand->getKey())->select('id'),
        );

        foreach ($assets->cursor() as $asset) {
            UploadService::freeModel($asset);
        }
    }

    protected function logName(): string
    {
        return 'brands';
    }

    /**
     * `content` is every locale's full HTML, and updated() writes both sides of a
     * diff — logging it would put two copies of the body in every audit row.
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
        return ['name', 'slug', 'status', 'published_at'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
