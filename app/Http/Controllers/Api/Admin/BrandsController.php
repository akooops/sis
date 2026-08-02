<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Brand\BrandData;
use App\Data\Brand\StoreBrandData;
use App\Data\Brand\UpdateBrandData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Brand;
use App\Models\Language;
use App\Services\Uploads\UploadService;
use App\States\Brand\BrandStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BrandsController extends ApiController
{
    public function index(): JsonResponse
    {
        $brands = QueryBuilder::for(Brand::class)
            ->with('media')
            ->withCount('assetGroups')
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('status'),
                $this->searchTranslations(
                    ['id', 'name', 'slug'],
                    ['title', 'description'],
                    Language::enabledCodes(),
                ),
            ])
            ->allowedSorts(['id', 'name', 'slug', 'status', 'published_at', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(BrandData::collect($brands, PaginatedDataCollection::class), 'Brands retrieved successfully');
    }

    public function show(Brand $brand): JsonResponse
    {
        return $this->respond(BrandData::from($brand->loadCount('assetGroups')), 'Brand retrieved successfully');
    }

    public function store(StoreBrandData $data): JsonResponse
    {
        $default = Language::defaultCode();

        $brand = Brand::create([
            'name' => $data->name,
            'slug' => $data->slug,
            'title' => [$default => $data->title],
            'description' => [$default => $data->description],
            'content' => [$default => $data->content],
            'status' => BrandStatus::resolveStateClass($data->status),
            'published_at' => $data->status === 'published' ? now() : $data->published_at,
            'css_url' => $data->css_url,
            'custom_css' => $data->custom_css,
        ]);

        UploadService::attach($data->thumbnail, $brand, Brand::THUMBNAIL_COLLECTION);

        return $this->respond(BrandData::from($brand->fresh()->loadCount('assetGroups')), 'Brand created successfully', 201);
    }

    public function update(UpdateBrandData $data, Brand $brand): JsonResponse
    {
        $attributes = Arr::except($data->toArray(), ['status', 'thumbnail', 'published_at']);

        // mergeTranslations: the form only carries the enabled locales, so a plain
        // assignment would drop every disabled one.
        $brand->update($brand->mergeTranslations($attributes));

        $target = BrandStatus::resolveStateClass($data->status);

        if (! $brand->status instanceof $target) {
            try {
                $brand->status->transitionTo($target);
            } catch (TransitionNotFound) {
                throw ValidationException::withMessages([
                    'status' => "A {$brand->status->getValue()} brand cannot become {$data->status}.",
                ]);
            }
        }

        $brand->published_at = $data->status === 'published'
            ? ($brand->published_at ?? now())
            : $data->published_at;
        $brand->save();

        if (! $data->thumbnail instanceof Optional && $data->thumbnail) {
            UploadService::attach($data->thumbnail, $brand, Brand::THUMBNAIL_COLLECTION);
        }

        return $this->respond(BrandData::from($brand->fresh()->loadCount('assetGroups')), 'Brand updated successfully');
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $brand->delete();

        return $this->respond(null, 'Brand deleted successfully');
    }
}
