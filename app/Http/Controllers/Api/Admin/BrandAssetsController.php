<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\BrandAsset\BrandAssetData;
use App\Data\BrandAsset\ReorderBrandAssetsData;
use App\Data\BrandAsset\StoreBrandAssetData;
use App\Data\BrandAsset\UpdateBrandAssetData;
use App\Http\Controllers\Api\ApiController;
use App\Models\BrandAsset;
use App\Models\BrandAssetGroup;
use App\Models\Language;
use App\Services\Uploads\UploadService;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BrandAssetsController extends ApiController
{
    public function index(): JsonResponse
    {
        $assets = QueryBuilder::for(BrandAsset::class)
            // The DTO reads the file off the media relation on every row, and the
            // nested BrandData reads the brand's thumbnail off its own.
            ->with(['media', 'group.brand.media'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('brand_asset_group_id'),
                $this->brandFilter(),
                $this->searchTranslations(['id', 'name'], ['title'], Language::enabledCodes()),
            ])
            ->allowedSorts(['id', 'name', 'order', 'created_at'])
            // Display order by default: assets are a list under their group.
            ->defaultSort('order')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(BrandAssetData::collect($assets, PaginatedDataCollection::class), 'Brand assets retrieved successfully');
    }

    /** Nested route: the group comes from the URL, overwriting any filter the client sent. */
    public function forGroup(BrandAssetGroup $brandAssetGroup): JsonResponse
    {
        request()->merge(['filter' => array_merge((array) request()->input('filter', []), ['brand_asset_group_id' => $brandAssetGroup->id])]);

        return $this->index();
    }

    public function show(BrandAsset $brandAsset): JsonResponse
    {
        return $this->respond(BrandAssetData::from($brandAsset->load(['media', 'group.brand.media'])), 'Brand asset retrieved successfully');
    }

    public function store(StoreBrandAssetData $data): JsonResponse
    {
        $asset = BrandAsset::create([
            'brand_asset_group_id' => $data->brand_asset_group_id,
            'name' => $data->name,
            'order' => BrandAsset::nextOrder($data->brand_asset_group_id),
            'title' => [Language::defaultCode() => $data->title],
        ]);

        UploadService::attach($data->file, $asset, BrandAsset::FILE_COLLECTION);

        return $this->respond(BrandAssetData::from($asset->fresh()->load(['media', 'group.brand.media'])), 'Brand asset created successfully', 201);
    }

    public function update(UpdateBrandAssetData $data, BrandAsset $brandAsset): JsonResponse
    {
        $attributes = [
            'brand_asset_group_id' => $data->brand_asset_group_id,
            'name' => $data->name,
            'title' => $data->title,
        ];

        // Moved to another group: take a position that group has free, or it would
        // land on one already held over there.
        if ($data->brand_asset_group_id !== $brandAsset->brand_asset_group_id) {
            $attributes['order'] = BrandAsset::nextOrder($data->brand_asset_group_id);
        }

        // mergeTranslations: the form only carries the enabled locales, so a plain
        // assignment would drop every disabled one.
        $brandAsset->update($brandAsset->mergeTranslations($attributes));

        if (! $data->file instanceof Optional && $data->file) {
            UploadService::attach($data->file, $brandAsset, BrandAsset::FILE_COLLECTION);
        }

        return $this->respond(BrandAssetData::from($brandAsset->fresh()->load(['media', 'group.brand.media'])), 'Brand asset updated successfully');
    }

    public function destroy(BrandAsset $brandAsset): JsonResponse
    {
        $brandAsset->delete();

        return $this->respond(null, 'Brand asset deleted successfully');
    }

    /**
     * Position in the array is the order. Omitted ids keep theirs, which is why the
     * drawer submits the whole list — one group's list, since order is per group.
     *
     * Builder updates: position is presentation, and a row per drag would drown the
     * audit log.
     */
    public function reorder(ReorderBrandAssetsData $data): JsonResponse
    {
        foreach ($data->ids as $position => $id) {
            BrandAsset::query()->whereKey($id)->update(['order' => $position]);
        }

        return $this->respond(null, 'Brand assets reordered successfully');
    }

    /**
     * An asset has no brand_id of its own — the brand belongs to its group — so this
     * cannot be AllowedFilter::exact, which only compares columns on the queried
     * table. The whereHas keeps the brand page's asset list one request.
     */
    private function brandFilter(): AllowedFilter
    {
        return AllowedFilter::callback(
            'brand_id',
            fn ($query, $value) => $query->whereHas('group', fn ($group) => $group->where('brand_id', $value)),
        );
    }
}
