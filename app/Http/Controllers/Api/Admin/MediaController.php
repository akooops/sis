<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Media\MediaData;
use App\Data\Media\StoreMediaData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Media;
use App\Services\Uploads\UploadService;
use App\States\Media\Clean;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * The upload endpoint plus the library that browses what has been uploaded.
 * store() is the single door files come in through — every form posts here and
 * then submits only the returned id — and it is the one media route with no
 * permission gate, since any authenticated admin filling in a form needs it.
 * Nothing here writes to a disk directly: UploadService owns that.
 */
class MediaController extends ApiController
{
    /**
     * Browse media to reuse across models. `type` is a config/uploads.php
     * category key — images/documents/videos/audio, plural — not a mime group.
     * `clean=true` narrows to scan-cleared media, which is the only set a picker
     * should offer: anything else may still be sitting in quarantine.
     */
    public function index(): JsonResponse
    {
        $media = QueryBuilder::for(Media::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                // Accepts a single type or a comma-separated list (e.g. images,videos)
                // so a restricted picker can show "all of its allowed types".
                AllowedFilter::callback('type', function ($query, $value) {
                    $types = array_filter(array_map('trim', is_array($value) ? $value : explode(',', (string) $value)));
                    if ($types) {
                        $query->whereIn('custom_properties->type', $types);
                    }
                }),
                AllowedFilter::callback('clean', fn ($query, $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
                    ? $query->whereState('state', Clean::class)
                    : $query),
                $this->search(['name', 'file_name']),
            ])
            ->allowedSorts(['id', 'name', 'size', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(MediaData::collect($media, PaginatedDataCollection::class), 'Media retrieved successfully');
    }

    public function store(StoreMediaData $data): JsonResponse
    {
        return $this->respond(UploadService::store($data), 'File uploaded successfully', 201);
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
