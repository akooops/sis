<?php

namespace App\Data\JobOfferCluster;

use App\Data\JobOffer\JobOfferData;
use App\Models\JobOfferCluster;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * One posting's placement in one pool.
 *
 * The mirror of CandidateClusterData, and it exists because postings and
 * candidates share ONE embedding space: a posting is placed by cosine against the
 * same centroids the candidates were clustered into, so there is no second
 * taxonomy to keep in step.
 *
 * `distance` is null for anything the rebuild did not place. No Store twin — see
 * CandidateClusterData.
 */
class JobOfferClusterData extends Data
{
    public function __construct(
        public string $id,
        public string $cluster_id,
        public string $job_offer_id,
        public ?float $distance,
        public ?float $similarity,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|JobOfferData|null $job_offer,
    ) {}

    public static function fromModel(JobOfferCluster $link): self
    {
        return new self(
            id: $link->id,
            cluster_id: $link->cluster_id,
            job_offer_id: $link->job_offer_id,
            distance: $link->distance !== null ? (float) $link->distance : null,
            similarity: $link->distance !== null ? round(1 - (float) $link->distance, 4) : null,
            created_at: $link->created_at?->toIso8601String(),
            updated_at: $link->updated_at?->toIso8601String(),
            job_offer: Lazy::whenLoaded('jobOffer', $link, fn () => $link->jobOffer ? JobOfferData::from($link->jobOffer) : null),
        );
    }
}
