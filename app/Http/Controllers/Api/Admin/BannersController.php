<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Banner\BannerData;
use App\Data\Banner\ReorderBannersData;
use App\Data\Banner\StoreBannerData;
use App\Data\Banner\UpdateBannerData;
use App\Enums\MorphType;
use App\Http\Controllers\Api\ApiController;
use App\Models\Banner;
use App\Models\Language;
use App\Services\Uploads\UploadService;
use App\States\Banner\BannerStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BannersController extends ApiController
{
    public function index(): JsonResponse
    {
        $banners = QueryBuilder::for(Banner::class)
            ->with(['media', 'linkable'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('status'),
                $this->morphType('linkable_type'),
                $this->searchTranslations(
                    ['id', 'name', 'url'],
                    ['title', 'cta'],
                    Language::enabledCodes(),
                ),
            ])
            ->allowedSorts(['id', 'name', 'order', 'status', 'published_at', 'created_at'])
            // Display order by default: this is a carousel, not a log.
            ->defaultSort('order')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(BannerData::collect($banners, PaginatedDataCollection::class), 'Banners retrieved successfully');
    }

    public function show(Banner $banner): JsonResponse
    {
        return $this->respond(BannerData::from($banner->load(['media', 'linkable'])), 'Banner retrieved successfully');
    }

    public function store(StoreBannerData $data): JsonResponse
    {
        $default = Language::defaultCode();

        $banner = Banner::create([
            'name' => $data->name,
            'order' => Banner::nextOrder(),
            'status' => BannerStatus::resolveStateClass($data->status),
            'published_at' => $data->status === 'published' ? now() : $data->published_at,
            'url' => $data->url,
            // The column stores the class; the API only ever speaks the alias.
            'linkable_type' => MorphType::classFor($data->linkable_type),
            'linkable_id' => $data->linkable_id,
            'title' => [$default => $data->title],
            'cta' => [$default => $data->cta],
        ]);

        UploadService::attach($data->thumbnail, $banner, Banner::THUMBNAIL_COLLECTION);

        if ($data->video) {
            UploadService::attach($data->video, $banner, Banner::VIDEO_COLLECTION);
        }

        return $this->respond(BannerData::from($banner->fresh()->load(['media', 'linkable'])), 'Banner created successfully', 201);
    }

    public function update(UpdateBannerData $data, Banner $banner): JsonResponse
    {
        // mergeTranslations: the form only carries the enabled locales, so a plain
        // assignment would drop every disabled one.
        $banner->update($banner->mergeTranslations([
            'name' => $data->name,
            // Not Optional, so nulls come through and actually clear the link.
            'url' => $data->url,
            'linkable_type' => MorphType::classFor($data->linkable_type),
            'linkable_id' => $data->linkable_id,
            'title' => $data->title,
            'cta' => $data->cta,
        ]));

        $target = BannerStatus::resolveStateClass($data->status);

        if (! $banner->status instanceof $target) {
            try {
                $banner->status->transitionTo($target);
            } catch (TransitionNotFound) {
                throw ValidationException::withMessages([
                    'status' => "A {$banner->status->getValue()} banner cannot become {$data->status}.",
                ]);
            }
        }

        $banner->published_at = $data->status === 'published'
            ? ($banner->published_at ?? now())
            : $data->published_at;
        $banner->save();

        if (! $data->thumbnail instanceof Optional && $data->thumbnail) {
            UploadService::attach($data->thumbnail, $banner, Banner::THUMBNAIL_COLLECTION);
        }

        if (! $data->video instanceof Optional && $data->video) {
            UploadService::attach($data->video, $banner, Banner::VIDEO_COLLECTION);
        }

        return $this->respond(BannerData::from($banner->fresh()->load(['media', 'linkable'])), 'Banner updated successfully');
    }

    public function destroy(Banner $banner): JsonResponse
    {
        $banner->delete();

        return $this->respond(null, 'Banner deleted successfully');
    }

    /**
     * Position in the array is the order. Omitted ids keep theirs, which is why the
     * drawer submits the whole list.
     *
     * Builder updates: position is presentation, and a row per drag would drown the
     * audit log.
     */
    public function reorder(ReorderBannersData $data): JsonResponse
    {
        foreach ($data->ids as $position => $id) {
            Banner::query()->whereKey($id)->update(['order' => $position]);
        }

        return $this->respond(null, 'Banners reordered successfully');
    }
}
