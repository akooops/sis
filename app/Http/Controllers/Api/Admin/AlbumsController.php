<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Album\AlbumData;
use App\Data\Album\StoreAlbumData;
use App\Data\Album\UpdateAlbumData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Album;
use App\Models\Language;
use App\Services\Uploads\UploadService;
use App\States\Album\AlbumStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AlbumsController extends ApiController
{
    public function index(): JsonResponse
    {
        $albums = QueryBuilder::for(Album::class)
            // Both thumbnail_url and files_count read media, so one eager load
            // serves the whole page rather than two queries per row.
            ->with('media')
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

        return $this->respond(AlbumData::collect($albums, PaginatedDataCollection::class), 'Albums retrieved successfully');
    }

    public function show(Album $album): JsonResponse
    {
        return $this->respond(AlbumData::from($album->load('media')), 'Album retrieved successfully');
    }

    public function store(StoreAlbumData $data): JsonResponse
    {
        $default = Language::defaultCode();

        $album = Album::create([
            'name' => $data->name,
            'slug' => $data->slug,
            'title' => [$default => $data->title],
            'description' => [$default => $data->description],
            'content' => [$default => $data->content],
            'status' => AlbumStatus::resolveStateClass($data->status),
            'published_at' => $data->status === 'published' ? now() : $data->published_at,
            'css_url' => $data->css_url,
            'custom_css' => $data->custom_css,
        ]);

        UploadService::attach($data->thumbnail, $album, Album::THUMBNAIL_COLLECTION);

        // The gallery, in submitted order — sync writes order_column from it.
        UploadService::sync($data->files, $album, Album::FILES_COLLECTION);

        return $this->respond(AlbumData::from($album->fresh()->load('media')), 'Album created successfully', 201);
    }

    public function update(UpdateAlbumData $data, Album $album): JsonResponse
    {
        $album->update(Arr::except($data->toArray(), ['status', 'thumbnail', 'published_at', 'files']));

        $target = AlbumStatus::resolveStateClass($data->status);

        if (! $album->status instanceof $target) {
            try {
                $album->status->transitionTo($target);
            } catch (TransitionNotFound) {
                throw ValidationException::withMessages([
                    'status' => "A {$album->status->getValue()} album cannot become {$data->status}.",
                ]);
            }
        }

        $album->published_at = $data->status === 'published'
            ? ($album->published_at ?? now())
            : $data->published_at;
        $album->save();

        if (! $data->thumbnail instanceof Optional && $data->thumbnail) {
            UploadService::attach($data->thumbnail, $album, Album::THUMBNAIL_COLLECTION);
        }

        // Removing a file from the gallery drops its id here, which detaches it
        // back into the media library rather than deleting it.
        UploadService::sync($data->files, $album, Album::FILES_COLLECTION);

        return $this->respond(AlbumData::from($album->fresh()->load('media')), 'Album updated successfully');
    }

    public function destroy(Album $album): JsonResponse
    {
        $album->delete();

        return $this->respond(null, 'Album deleted successfully');
    }
}
