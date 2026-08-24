<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\FacilityAlbum\FacilityAlbumData;
use App\Data\FacilityAlbum\StoreFacilityAlbumData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Facility;
use App\Models\FacilityAlbum;
use App\Models\Language;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Which photo albums appear on a venue's page.
 *
 * A PIVOT RESOURCE, read and written through the pivot MODEL — never through
 * Facility::albums()->attach(). That relation is read-only: the table carries
 * its own ULID primary key with no database default, so attach()'s raw insert
 * never generates one and MySQL rejects the row. Going through the model is also
 * what makes FacilityAlbumObserver fire, so the change is audited against the
 * facility rather than vanishing.
 *
 * No update: the link is two foreign keys. Attaching and detaching is the whole
 * vocabulary, which is why there is no `.update` permission either.
 */
class FacilityAlbumsController extends ApiController
{
    public function index(Facility $facility): JsonResponse
    {
        $links = QueryBuilder::for($facility->albumLinks()->with('album.media'))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('album_id'),
                // The drawer's search box: the album's own name and slug plus
                // its translated title, which is what an admin actually reads.
                $this->searchRelationByColumns('album', ['id', 'name', 'slug']),
            ])
            ->allowedIncludes(['album', 'facility'])
            ->allowedSorts(['id', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(FacilityAlbumData::collect($links, PaginatedDataCollection::class), 'Facility albums retrieved successfully');
    }

    /**
     * Attach several at once — what PivotDrawer's multi-select posts.
     *
     * firstOrCreate on the HasMany, so re-attaching something already linked is a
     * no-op rather than a unique-constraint violation; `wasRecentlyCreated` keeps
     * the response to the rows this call genuinely made.
     */
    public function store(StoreFacilityAlbumData $data, Facility $facility): JsonResponse
    {
        $created = collect($data->albums)
            ->map(fn (string $albumId) => $facility->albumLinks()->firstOrCreate(['album_id' => $albumId]))
            ->filter->wasRecentlyCreated
            ->values();

        $created->each->load('album.media');

        return $this->respond(FacilityAlbumData::collect($created->all()), 'Albums attached successfully', 201);
    }

    /**
     * Detach one. The album itself is untouched — it keeps its URL, its listing
     * and its content; only the association goes.
     */
    public function destroy(FacilityAlbum $facilityAlbum): JsonResponse
    {
        $facilityAlbum->delete();

        return $this->respond(null, 'Album detached successfully');
    }
}
