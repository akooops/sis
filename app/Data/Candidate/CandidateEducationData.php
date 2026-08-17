<?php

namespace App\Data\Candidate;

use App\Models\CandidateEducation;
use Spatie\LaravelData\Data;

/**
 * One qualification, as projected out of the application form's education group.
 *
 * Read-only: every field here is written by ApplicationProjector::replaceGroups()
 * and replaced wholesale on the next application, so there is no Store/Update
 * twin. `order` is exposed because it is the reading order the applicant chose,
 * and the relation is already sorted by it.
 */
class CandidateEducationData extends Data
{
    public function __construct(
        public string $id,
        public string $institution,
        public ?string $degree,
        public ?string $field_of_study,
        public ?int $start_year,
        public ?int $end_year,
        public ?string $description,
        public int $order,
    ) {}

    public static function fromModel(CandidateEducation $education): self
    {
        return new self(
            id: $education->id,
            institution: $education->institution,
            degree: $education->degree,
            field_of_study: $education->field_of_study,
            start_year: $education->start_year,
            end_year: $education->end_year,
            description: $education->description,
            order: (int) $education->order,
        );
    }
}
