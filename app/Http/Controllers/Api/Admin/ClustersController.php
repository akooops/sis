<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Cluster\ClusterData;
use App\Data\Cluster\UpdateClusterData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Cluster;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Talent pools — DISCOVERED, never declared.
 *
 * NO store(), and that is the model rather than an omission: a pool is the output
 * of a nightly k-means over candidate embeddings, so a hand-made one would have no
 * centroid and could therefore neither gather members nor place a posting. There
 * is no `clusters.store` permission either.
 *
 * What an admin CAN do is name one and lock the name so the next rebuild keeps
 * it, or delete a pool that has stopped meaning anything.
 */
class ClustersController extends ApiController
{
    public function index(): JsonResponse
    {
        $clusters = QueryBuilder::for(Cluster::class)
            /*
             * The LIVE counts, alongside the rebuild's own `size`.
             *
             * They disagree on purpose: `size` is what the last rebuild recorded
             * and stays frozen for a pool the maths did not reproduce, while
             * removing a member by hand moves the pivot and not the column.
             * Rendering only `size` would show a number that is no longer true.
             */
            ->withCount(['candidateLinks', 'jobOfferLinks'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('is_locked'),
                AllowedFilter::callback('size_min', fn ($query, $value) => $query->where('size', '>=', (int) $value)),
                AllowedFilter::callback('size_max', fn ($query, $value) => $query->where('size', '<=', (int) $value)),
                $this->search(['name', 'description']),
            ])
            ->allowedSorts(['id', 'name', 'size', 'rebuilt_at', 'created_at'])
            // `size` is indexed, and the biggest pool is the one worth reading first.
            ->defaultSort('-size')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(ClusterData::collect($clusters, PaginatedDataCollection::class), 'Talent pools retrieved successfully');
    }

    public function show(Cluster $cluster): JsonResponse
    {
        $cluster->loadCount(['candidateLinks', 'jobOfferLinks']);

        return $this->respond(ClusterData::from($cluster), 'Talent pool retrieved successfully');
    }

    public function update(UpdateClusterData $data, Cluster $cluster): JsonResponse
    {
        // Dumb on purpose: the form decides whether a rename should be locked
        // (it defaults the box to checked), and this applies what it is sent.
        $cluster->update($data->toArray());

        return $this->respond(
            ClusterData::from($cluster->fresh()->loadCount(['candidateLinks', 'jobOfferLinks'])),
            'Talent pool updated successfully',
        );
    }

    public function destroy(Cluster $cluster): JsonResponse
    {
        // Memberships cascade. Nothing else does: deleting a pool loses the
        // grouping, never a candidate or a posting.
        $cluster->delete();

        return $this->respond(null, 'Talent pool deleted successfully');
    }
}
