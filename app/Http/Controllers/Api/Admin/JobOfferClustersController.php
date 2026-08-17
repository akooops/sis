<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\JobOfferCluster\JobOfferClusterData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Cluster;
use App\Models\JobOfferCluster;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Which postings a pool covers — READ AND REMOVE, with no add.
 *
 * The mirror of CandidateClustersController, and unbuildable in the other
 * direction for the same reason: RebuildClusters::assignOffers() opens with
 * `JobOfferCluster::query()->delete()`, so a hand-placed posting survives until
 * the next run and no longer.
 *
 * Postings are placed by cosine against the SAME centroids the candidates were
 * clustered into — one shared embedding space, so there is no second taxonomy
 * here to keep in step with the other controller.
 */
class JobOfferClustersController extends ApiController
{
    public function index(Cluster $cluster): JsonResponse
    {
        $postings = QueryBuilder::for($cluster->jobOfferLinks()->with('jobOffer'))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('job_offer_id'),
                $this->searchRelationByColumns('jobOffer', ['id', 'name', 'slug']),
            ])
            /*
             * `job_offer` as well as `jobOffer`: PivotDrawer sends its `relation`
             * prop straight through as the include, and that prop also names the
             * key it reads a cell out of — which is the DTO's `job_offer`. Without
             * the alias the drawer's every request is a 400 from the query builder.
             */
            ->allowedIncludes([
                AllowedInclude::relationship('job_offer', 'jobOffer'),
                AllowedInclude::relationship('jobOffer'),
                AllowedInclude::relationship('cluster'),
            ])
            ->allowedSorts(['id', 'distance', 'created_at'])
            // Same NULL-first trap as the candidate pivot — see that controller.
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(JobOfferClusterData::collect($postings, PaginatedDataCollection::class), 'Pool postings retrieved successfully');
    }

    public function destroy(JobOfferCluster $jobOfferCluster): JsonResponse
    {
        $cluster = $jobOfferCluster->cluster;
        $name = $jobOfferCluster->jobOffer?->name;

        $jobOfferCluster->delete();

        // Audited against the pool by hand — see CandidateClustersController for why.
        if ($cluster) {
            activity('clusters')
                ->performedOn($cluster)
                ->event('detached')
                ->withProperties(['meta' => ['name' => $name ?? $jobOfferCluster->job_offer_id]])
                ->log('detached');
        }

        return $this->respond(null, 'Posting removed from the pool successfully');
    }
}
