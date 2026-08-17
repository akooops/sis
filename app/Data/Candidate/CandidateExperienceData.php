<?php

namespace App\Data\Candidate;

use App\Models\CandidateExperience;
use Spatie\LaravelData\Data;

/**
 * One job someone has held.
 *
 * `is_current` is carried separately from `end_year` rather than inferred from a
 * null one: "still there" and "never said when they left" are different answers,
 * and Candidate::yearsOfExperience() counts a current role up to today because of
 * it.
 */
class CandidateExperienceData extends Data
{
    public function __construct(
        public string $id,
        public string $company_name,
        public ?string $job_title,
        public ?int $start_year,
        public ?int $end_year,
        public bool $is_current,
        public ?string $description,
        public int $order,
    ) {}

    public static function fromModel(CandidateExperience $experience): self
    {
        return new self(
            id: $experience->id,
            company_name: $experience->company_name,
            job_title: $experience->job_title,
            start_year: $experience->start_year,
            end_year: $experience->end_year,
            is_current: (bool) $experience->is_current,
            description: $experience->description,
            order: (int) $experience->order,
        );
    }
}
