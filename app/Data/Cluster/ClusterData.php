<?php

namespace App\Data\Cluster;

use App\Models\Cluster;
use Spatie\LaravelData\Data;

/**
 * A talent pool.
 *
 * `size` AND the live counts are BOTH here, and that is deliberate. `size` is
 * what the nightly rebuild recorded, frozen for any pool the maths did not
 * reproduce; the counts come from withCount() and move when a person removes a
 * member by hand. Showing only one of them would be a lie in one direction or the
 * other, so the table renders the live count and the drawer shows what the
 * rebuild believed.
 *
 * THE CENTROID NEVER LEAVES THE SERVER — 256 floats describing nothing a human
 * reads. `has_centroid` is the whole of what the UI needs: a pool without one was
 * rebuilt from nothing and cannot place a posting.
 */
class ClusterData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public int $size,
        public ?int $candidates_count,
        public ?int $job_offers_count,
        public bool $is_locked,
        public bool $has_centroid,
        public ?string $rebuilt_at,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Cluster $cluster): self
    {
        return new self(
            id: $cluster->id,
            // NOT NULL in the schema; RebuildClusters names a new pool
            // 'Unnamed pool' when the LLM cannot, so this is never blank.
            name: $cluster->name,
            description: $cluster->description,
            size: (int) $cluster->size,
            // withCount() only. Null means the caller did not ask, which is not
            // the same as an empty pool.
            candidates_count: $cluster->candidate_links_count !== null ? (int) $cluster->candidate_links_count : null,
            job_offers_count: $cluster->job_offer_links_count !== null ? (int) $cluster->job_offer_links_count : null,
            is_locked: (bool) $cluster->is_locked,
            has_centroid: $cluster->centroid !== null,
            rebuilt_at: $cluster->rebuilt_at?->toIso8601String(),
            created_at: $cluster->created_at?->toIso8601String(),
            updated_at: $cluster->updated_at?->toIso8601String(),
        );
    }
}
