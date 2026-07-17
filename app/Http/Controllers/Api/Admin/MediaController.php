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

class MediaController extends ApiController
{
    public function index(): JsonResponse
    {
        $media = QueryBuilder::for(Media::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
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

    public function detach(Media $media): JsonResponse
    {
        UploadService::detach($media);

        return $this->respond(null, 'Media detached successfully');
    }
}
