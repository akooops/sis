<?php

namespace App\Data\Candidate;

use App\Models\JobApplication;
use Spatie\LaravelData\Data;

/**
 * One of a candidate's applications, as the candidate drawer lists them.
 *
 * A DELIBERATELY SMALLER SHAPE THAN JobApplicationData, and not a reuse of it,
 * for two reasons. The first is a cycle: JobApplicationData carries a Lazy
 * CandidateData, so nesting it under CandidateData would make the two refer to
 * each other. The second is that this list wants three facts — which posting,
 * where it got to, and when — and a full application DTO here would ship the
 * match score twice on one screen.
 */
class CandidateApplicationData extends Data
{
    public function __construct(
        public string $id,
        public string $job_offer_id,
        public ?string $job_offer_name,
        public string $status,
        public ?string $applied_at,
    ) {}

    public static function fromModel(JobApplication $application): self
    {
        return new self(
            id: $application->id,
            job_offer_id: $application->job_offer_id,
            job_offer_name: $application->relationLoaded('jobOffer') ? $application->jobOffer?->name : null,
            status: $application->status->getValue(),
            applied_at: $application->applied_at?->toIso8601String(),
        );
    }
}
