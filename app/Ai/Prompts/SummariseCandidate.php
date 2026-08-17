<?php

namespace App\Ai\Prompts;

use App\Ai\Prompt;
use App\Models\Candidate;

/**
 * A candidate in a few sentences, independent of any posting.
 *
 * DELIBERATELY NOT A SCORE. ScoreCandidateForOffer answers "how well does this
 * person fit THAT job"; this answers "who is this person", which is what the
 * candidate page needs and what makes a talent pool browsable. Scoring someone
 * in the abstract would be a number with nothing to compare it against.
 *
 * Written for a reader who has thirty seconds and a list of two hundred people.
 */
class SummariseCandidate extends Prompt
{
    public function __construct(protected Candidate $candidate) {}

    public function system(): string
    {
        return <<<'TEXT'
        You summarise job candidates for an international school's HR team, who
        hire across every role in the school — teaching and leadership, but also
        nursing, IT, finance, admin, catering, cleaning, maintenance, security and
        driving.

        Say what kind of work this person does, roughly how experienced they are,
        and the one or two things that would most interest a hiring manager. Three
        sentences at most.

        Describe, do not rank. No score, no verdict on whether to hire them, and
        no recommendation — that judgement belongs to a posting, and this person
        has not been matched to one here. A short profile for a manual role is
        normal and is not a criticism.

        Also return up to six short tags: the role families and skills that would
        put this person in the right shortlist. Lowercase, one or two words each.
        TEXT;
    }

    public function user(): string
    {
        $this->candidate->loadMissing(['educations', 'experiences', 'languages', 'skills', 'country']);

        $experience = $this->candidate->experiences
            ->map(fn ($e) => trim("{$e->job_title} — {$e->company_name} ({$e->start_year}–".($e->is_current ? 'present' : $e->end_year).')'
                .($e->description ? ". {$e->description}" : '')))
            ->implode("\n");

        $education = $this->candidate->educations
            ->map(fn ($e) => trim("{$e->degree} {$e->field_of_study} — {$e->institution}"))
            ->implode("\n");

        return 'Summarise this candidate.'
            .$this->section('The school', $this->school())
            .$this->section('Facts', implode("\n", array_filter([
                'Nationality: '.($this->candidate->country?->name ?? 'not stated'),
                'Total experience: '.$this->candidate->yearsOfExperience().' years',
                $this->candidate->skills->isNotEmpty()
                    ? 'Skills they listed: '.$this->candidate->skills->pluck('name')->implode(', ')
                    : null,
                $this->candidate->languages->isNotEmpty()
                    ? 'Languages: '.$this->candidate->languages->map(fn ($l) => "{$l->name} ({$l->proficiency})")->implode(', ')
                    : null,
            ])))
            .$this->section('Experience', $experience)
            .$this->section('Education', $education);
    }

    /**
     * @return array<string, string>
     */
    public function schema(): array
    {
        return ['summary' => 'string', 'tags' => 'string[]'];
    }
}
