<?php

namespace App\Data\Candidate;

use App\Models\CandidateSkill;
use Spatie\LaravelData\Data;

/**
 * One skill, in the candidate's own wording.
 *
 * `fold` is deliberately ABSENT. It is the lowercased comparison form the unique
 * index and every lookup use — an internal detail, and showing it would put "ib"
 * next to "IB" on screen for no reason. The `skill` filter folds its own input,
 * so nothing outside the model needs to know the column exists.
 */
class CandidateSkillData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}

    public static function fromModel(CandidateSkill $skill): self
    {
        return new self(
            id: $skill->id,
            name: $skill->name,
        );
    }
}
