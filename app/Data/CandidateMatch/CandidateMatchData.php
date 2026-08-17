<?php

namespace App\Data\CandidateMatch;

use App\Models\CandidateMatch;
use Spatie\LaravelData\Data;

/**
 * How well one candidate fits one posting — read from either end.
 *
 * APPLIED VS MERELY RECOMMENDED IS THE POINT. A candidate_matches row exists for
 * every pair the scorer evaluated, whether or not anyone applied; the difference
 * is simply whether a job_applications row exists for the same pair. The
 * controller computes that with two correlated subselects rather than a second
 * table, and it arrives here as `application_id` / `application_status` /
 * `has_applied`.
 *
 * `score` IS NEVER COALESCED, and `is_strong` is false for a null one — so the UI
 * must key its empty state on `is_scored`, never on `! is_strong`. Null means the
 * queue has not reached this pair; 0 means the model judged them a poor fit, and
 * presenting the first as the second is exactly what scopeStrong's whereNotNull
 * exists to prevent.
 */
class CandidateMatchData extends Data
{
    public function __construct(
        public string $id,
        public string $candidate_id,
        public string $job_offer_id,
        public ?int $score,
        public ?string $comment,
        public ?string $matched_at,
        public ?string $created_at,
        public ?string $updated_at,
        public bool $is_scored,
        public bool $is_strong,
        public int $shortlist_threshold,
        public ?string $application_id,
        public ?string $application_status,
        public bool $has_applied,
        public ?string $candidate_name,
        public ?string $candidate_email,
        public bool $has_cv,
        public ?string $job_offer_name,
    ) {}

    public static function fromModel(CandidateMatch $match): self
    {
        $candidate = $match->relationLoaded('candidate') ? $match->candidate : null;

        // Written by the controller's addSelect(), absent when a caller built the
        // model some other way.
        $applicationId = $match->getAttribute('application_id');

        return new self(
            id: $match->id,
            candidate_id: $match->candidate_id,
            job_offer_id: $match->job_offer_id,
            score: $match->score,
            comment: $match->comment,
            matched_at: $match->matched_at?->toIso8601String(),
            created_at: $match->created_at?->toIso8601String(),
            updated_at: $match->updated_at?->toIso8601String(),
            is_scored: $match->score !== null,
            is_strong: $match->score !== null && $match->score >= CandidateMatch::SHORTLIST_THRESHOLD,
            // Sent down so no client hardcodes 60 — the threshold is HR's to tune.
            shortlist_threshold: CandidateMatch::SHORTLIST_THRESHOLD,
            application_id: $applicationId,
            application_status: $match->getAttribute('application_status'),
            has_applied: $applicationId !== null,
            candidate_name: $candidate?->full_name,
            candidate_email: $candidate?->email,
            has_cv: $candidate?->cv_media_id !== null,
            job_offer_name: $match->relationLoaded('jobOffer') ? $match->jobOffer?->name : null,
        );
    }
}
