<?php

namespace App\Data\CandidateCluster;

use App\Data\Candidate\CandidateData;
use App\Models\CandidateCluster;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * One candidate's membership of one pool.
 *
 * `distance` is the cosine distance to the pool's centroid, written by the nightly
 * rebuild — and NULL for anything not placed by it. `similarity` is the same
 * number the way a person reads it, so the drawer does not have to know that
 * lower is better.
 *
 * There is no Store twin: the pool drawers are read-and-remove. The rebuild
 * deletes every membership row before reassigning, so a hand-added member would
 * live until 03:00 and no longer.
 */
class CandidateClusterData extends Data
{
    public function __construct(
        public string $id,
        public string $cluster_id,
        public string $candidate_id,
        public ?float $distance,
        public ?float $similarity,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|CandidateData|null $candidate,
    ) {}

    public static function fromModel(CandidateCluster $link): self
    {
        return new self(
            id: $link->id,
            cluster_id: $link->cluster_id,
            candidate_id: $link->candidate_id,
            distance: $link->distance !== null ? (float) $link->distance : null,
            similarity: $link->distance !== null ? round(1 - (float) $link->distance, 4) : null,
            created_at: $link->created_at?->toIso8601String(),
            updated_at: $link->updated_at?->toIso8601String(),
            candidate: Lazy::whenLoaded('candidate', $link, fn () => $link->candidate ? CandidateData::from($link->candidate) : null),
        );
    }
}
