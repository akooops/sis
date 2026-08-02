<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\BrandAssetGroup\BrandAssetGroupData;
use App\Data\BrandAssetGroup\ReorderBrandAssetGroupsData;
use App\Data\BrandAssetGroup\StoreBrandAssetGroupData;
use App\Data\BrandAssetGroup\UpdateBrandAssetGroupData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Brand;
use App\Models\BrandAssetGroup;
use App\Models\Language;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BrandAssetGroupsController extends ApiController
{
    public function index(): JsonResponse
    {
        $groups = QueryBuilder::for(BrandAssetGroup::class)
            ->with('brand.media')
            ->withCount('assets')
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('brand_id'),
                $this->searchTranslations(
                    ['id', 'name'],
                    ['title'],
                    Language::enabledCodes(),
                ),
            ])
            ->allowedSorts(['id', 'name', 'order', 'created_at'])
            // Display order by default: groups are a strip under their brand.
            ->defaultSort('order')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(BrandAssetGroupData::collect($groups, PaginatedDataCollection::class), 'Brand asset groups retrieved successfully');
    }

    /** Nested route: the brand comes from the URL, overwriting any filter the client sent. */
    public function forBrand(Brand $brand): JsonResponse
    {
        request()->merge(['filter' => array_merge((array) request()->input('filter', []), ['brand_id' => $brand->id])]);

        return $this->index();
    }

    public function show(BrandAssetGroup $brandAssetGroup): JsonResponse
    {
        return $this->respond(
            BrandAssetGroupData::from($brandAssetGroup->load('brand.media')->loadCount('assets')),
            'Brand asset group retrieved successfully',
        );
    }

    public function store(StoreBrandAssetGroupData $data): JsonResponse
    {
        $default = Language::defaultCode();

        $group = BrandAssetGroup::create([
            'brand_id' => $data->brand_id,
            'name' => $data->name,
            'order' => BrandAssetGroup::nextOrder($data->brand_id),
            'title' => [$default => $data->title],
        ]);

        return $this->respond(
            BrandAssetGroupData::from($group->load('brand.media')->loadCount('assets')),
            'Brand asset group created successfully',
            201,
        );
    }

    public function update(UpdateBrandAssetGroupData $data, BrandAssetGroup $brandAssetGroup): JsonResponse
    {
        $attributes = $data->toArray();

        // Moved to another brand: take a position that brand has free, or it would
        // land on one already held over there.
        if ($data->brand_id !== $brandAssetGroup->brand_id) {
            $attributes['order'] = BrandAssetGroup::nextOrder($data->brand_id);
        }

        // mergeTranslations: the form only carries the enabled locales, so a plain
        // assignment would drop every disabled one.
        $brandAssetGroup->update($brandAssetGroup->mergeTranslations($attributes));

        return $this->respond(
            BrandAssetGroupData::from($brandAssetGroup->fresh()->load('brand.media')->loadCount('assets')),
            'Brand asset group updated successfully',
        );
    }

    public function destroy(BrandAssetGroup $brandAssetGroup): JsonResponse
    {
        $brandAssetGroup->delete();

        return $this->respond(null, 'Brand asset group deleted successfully');
    }

    /**
     * Position in the array is the order. Omitted ids keep theirs, which is why the
     * drawer submits the whole list — one brand's list, since order is per brand.
     *
     * Builder updates: position is presentation, and a row per drag would drown the
     * audit log.
     */
    public function reorder(ReorderBrandAssetGroupsData $data): JsonResponse
    {
        foreach ($data->ids as $position => $id) {
            BrandAssetGroup::query()->whereKey($id)->update(['order' => $position]);
        }

        return $this->respond(null, 'Brand asset groups reordered successfully');
    }
}
