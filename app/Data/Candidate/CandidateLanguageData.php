<?php

namespace App\Data\Candidate;

use App\Models\CandidateLanguage;
use Spatie\LaravelData\Data;

/**
 * One language and how well it is spoken.
 *
 * `proficiency` is a raw string, not an enum: Candidate::PROFICIENCIES is a plain
 * list so adding a level is a deploy rather than an ALTER, and it is nullable
 * because the form does not force an answer. The admin maps it to a label.
 */
class CandidateLanguageData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $proficiency,
        public int $order,
    ) {}

    public static function fromModel(CandidateLanguage $language): self
    {
        return new self(
            id: $language->id,
            name: $language->name,
            proficiency: $language->proficiency,
            order: (int) $language->order,
        );
    }
}
