<?php

namespace App\Data\JobApplication;

use App\Data\Candidate\CandidateData;
use App\Models\JobApplication;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * Output DTO for one act of applying.
 *
 * FLAT SCALARS FOR THE TABLE, one Lazy nested DTO for the drawer: a queue row
 * needs the applicant's name and the posting's, not two nested objects it would
 * have to reach through.
 *
 * THE SCORE IS READ THROUGH match(), BEHIND A relationLoaded() GUARD.
 * JobApplication::match() is a method rather than a relation — the row is
 * identified by two columns and Eloquent has no composite-key relation — and it
 * falls back to its own query when `candidateMatches` is not loaded. Without the
 * guard a 15-row index would fire 15 extra queries; with it, a caller that did
 * not eager-load simply gets null.
 *
 * `score` IS NEVER COALESCED. Null means the queue has not reached this pair yet;
 * 0 means the model judged them a poor fit. `is_scored` exists so the client has
 * a boolean it cannot accidentally derive from `score > 0`.
 */
class JobApplicationData extends Data
{
    public function __construct(
        public string $id,
        public string $candidate_id,
        public string $job_offer_id,
        public ?string $form_submission_id,
        public string $status,
        public ?string $applied_at,
        public ?string $created_at,
        public ?string $updated_at,
        public ?string $candidate_name,
        public ?string $candidate_email,
        public ?string $candidate_phone,
        public bool $has_cv,
        public ?string $job_offer_name,
        public ?int $score,
        public ?string $score_comment,
        public ?string $scored_at,
        public bool $is_scored,
        public Lazy|CandidateData|null $candidate,
    ) {}

    public static function fromModel(JobApplication $application): self
    {
        // Only when eager-loaded — see the class docblock.
        $match = $application->relationLoaded('candidateMatches') ? $application->match() : null;

        $candidate = $application->relationLoaded('candidate') ? $application->candidate : null;

        return new self(
            id: $application->id,
            candidate_id: $application->candidate_id,
            job_offer_id: $application->job_offer_id,
            form_submission_id: $application->form_submission_id,
            status: $application->status->getValue(),
            applied_at: $application->applied_at?->toIso8601String(),
            created_at: $application->created_at?->toIso8601String(),
            updated_at: $application->updated_at?->toIso8601String(),
            candidate_name: $candidate?->full_name,
            candidate_email: $candidate?->email,
            candidate_phone: $candidate?->phone,
            // A pointer check, so the list can flag a missing CV without loading
            // the media row per applicant.
            has_cv: $candidate?->cv_media_id !== null,
            job_offer_name: $application->relationLoaded('jobOffer') ? $application->jobOffer?->name : null,
            score: $match?->score,
            score_comment: $match?->comment,
            scored_at: $match?->matched_at?->toIso8601String(),
            is_scored: $match?->score !== null,
            /*
             * KEYED ON THE PROFILE, NOT ON `candidate` ITSELF.
             *
             * whenLoaded('candidate') would auto-include here, because the INDEX
             * eager-loads the candidate too — it needs the four flat fields above
             * and would otherwise fire a query per row. That put a whole nested
             * CandidateData on every queue row, which is exactly the payload the
             * flat scalars exist to avoid.
             *
             * `educations` is the marker for the deep load: only show() pulls the
             * profile, so only show() carries the nested record.
             */
            candidate: Lazy::when(
                fn () => $candidate?->relationLoaded('educations') ?? false,
                fn () => CandidateData::from($application->candidate),
            ),
        );
    }
}
