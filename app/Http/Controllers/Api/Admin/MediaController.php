<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Upload\MediaData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Media;
use App\Services\Uploads\UploadService;
use App\States\Media\Clean;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MediaController extends ApiController
{
    /**
     * Media library index — browse existing media to reuse across models.
     * Filter by type (image/document/video/audio), search by name, and
     * optionally restrict to scanned-clean media (the pickable set).
     */
    public function index(): JsonResponse
    {
        $media = QueryBuilder::for(Media::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::callback('type', fn ($query, $value) => $query->where('custom_properties->type', $value)),
                AllowedFilter::callback('clean', fn ($query, $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
                    ? $query->whereState('state', Clean::class)
                    : $query),
                $this->search(['name', 'file_name']),
            ])
            ->allowedSorts(['name', 'size', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(MediaData::collect($media, PaginatedDataCollection::class), 'Media retrieved successfully');
    }

    /**
     * Detach a media from its owner, returning it to the free (reusable) pool.
     * Nothing is deleted — the file is pruned later if it stays unused.
     */
    public function detach(Media $media): JsonResponse
    {
        UploadService::detach($media);

        return $this->respond(null, 'Media detached successfully');
    }
}
