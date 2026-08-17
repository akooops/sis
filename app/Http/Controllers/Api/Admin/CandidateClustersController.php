<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\CandidateCluster\CandidateClusterData;
use App\Http\Controllers\Api\ApiController;
use App\Models\CandidateCluster;
use App\Models\Cluster;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Who is in a pool — READ AND REMOVE, with no add.
 *
 * NO store(), and the reason is in RebuildClusters: assignCandidates() opens with
 * `CandidateCluster::query()->delete()` before it rewrites the table. Anything
 * added by hand would therefore live until 03:00 and no longer, which is a
 * feature that quietly undoes itself. `candidate-clusters.store` stays seeded and
 * inert until that job learns to preserve hand-made rows.
 *
 * A removal is transient for the same reason, and the drawer says so — but it is
 * still worth having: it takes someone out of a pool for the working day, which
 * is the horizon a shortlist is drawn on.
 */
class CandidateClustersController extends ApiController
{
    public function index(Cluster $cluster): JsonResponse
    {
        $members = QueryBuilder::for($cluster->candidateLinks()->with('candidate.country'))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('candidate_id'),
                $this->searchRelationByColumns('candidate', ['id', 'first_name', 'last_name', 'email']),
            ])
            ->allowedIncludes(['candidate', 'cluster'])
            ->allowedSorts(['id', 'distance', 'created_at'])
            /*
             * NOT `distance` ascending, tempting as nearest-to-centroid is:
             * `distance` is nullable for anything the rebuild did not place, and
             * MySQL puts NULL FIRST on ASC — so every hand-added member would
             * float to the top of a list sorted by fit. It stays available as a
             * sort the user picks, where the direction is their own choice.
             */
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(CandidateClusterData::collect($members, PaginatedDataCollection::class), 'Pool members retrieved successfully');
    }

    public function destroy(CandidateCluster $candidateCluster): JsonResponse
    {
        $cluster = $candidateCluster->cluster;
        $name = $candidateCluster->candidate?->full_name;

        $candidateCluster->delete();

        /*
         * A HAND-ROLLED activity(), which observers otherwise own.
         *
         * CandidateCluster is deliberately unobserved because the nightly rebuild
         * writes thousands of these rows and an observer cannot tell the machine
         * from the admin — it would fill the log with a record of a job doing its
         * job. But CLAUDE.md's promise is that what a PERSON does to a pool is
         * audited on the Cluster, and this is the only human write path there is,
         * so it logs itself. Third such exception, alongside
         * CsvSubmissionWriter::log() and UploadService::log().
         */
        if ($cluster) {
            activity('clusters')
                ->performedOn($cluster)
                ->event('detached')
                ->withProperties(['meta' => ['name' => $name ?? $candidateCluster->candidate_id]])
                ->log('detached');
        }

        return $this->respond(null, 'Candidate removed from the pool successfully');
    }
}
