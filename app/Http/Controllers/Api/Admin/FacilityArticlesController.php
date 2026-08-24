<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\FacilityArticle\FacilityArticleData;
use App\Data\FacilityArticle\StoreFacilityArticleData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Facility;
use App\Models\FacilityArticle;
use App\Models\Language;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Which news items appear on a venue's page.
 *
 * A PIVOT RESOURCE, read and written through the pivot MODEL — never through
 * Facility::articles()->attach(). That relation is read-only: the table carries
 * its own ULID primary key with no database default, so attach()'s raw insert
 * never generates one and MySQL rejects the row. Going through the model is also
 * what makes FacilityArticleObserver fire, so the change is audited against the
 * facility rather than vanishing.
 *
 * No update: the link is two foreign keys. Attaching and detaching is the whole
 * vocabulary, which is why there is no `.update` permission either.
 */
class FacilityArticlesController extends ApiController
{
    public function index(Facility $facility): JsonResponse
    {
        $links = QueryBuilder::for($facility->articleLinks()->with('article.media'))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('article_id'),
                // The drawer's search box: the article's own name and slug plus
                // its translated title, which is what an admin actually reads.
                $this->searchRelationByColumns('article', ['id', 'name', 'slug']),
            ])
            ->allowedIncludes(['article', 'facility'])
            ->allowedSorts(['id', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(FacilityArticleData::collect($links, PaginatedDataCollection::class), 'Facility articles retrieved successfully');
    }

    /**
     * Attach several at once — what PivotDrawer's multi-select posts.
     *
     * firstOrCreate on the HasMany, so re-attaching something already linked is a
     * no-op rather than a unique-constraint violation; `wasRecentlyCreated` keeps
     * the response to the rows this call genuinely made.
     */
    public function store(StoreFacilityArticleData $data, Facility $facility): JsonResponse
    {
        $created = collect($data->articles)
            ->map(fn (string $articleId) => $facility->articleLinks()->firstOrCreate(['article_id' => $articleId]))
            ->filter->wasRecentlyCreated
            ->values();

        $created->each->load('article.media');

        return $this->respond(FacilityArticleData::collect($created->all()), 'Articles attached successfully', 201);
    }

    /**
     * Detach one. The article itself is untouched — it keeps its URL, its listing
     * and its content; only the association goes.
     */
    public function destroy(FacilityArticle $facilityArticle): JsonResponse
    {
        $facilityArticle->delete();

        return $this->respond(null, 'Article detached successfully');
    }
}
