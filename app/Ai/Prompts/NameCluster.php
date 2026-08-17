<?php

namespace App\Ai\Prompts;

use App\Ai\Prompt;

/**
 * Give an emergent cluster a name a person can act on.
 *
 * WITHOUT THIS THE FEATURE IS UNUSABLE. k-means produces "cluster 7"; HR needs
 * "Primary maths and science". The clustering is what makes matching affordable,
 * but the name is what makes the pool something anyone will open.
 */
class NameCluster extends Prompt
{
    /**
     * @param  array<int, string>  $samples  a few members, described the same way
     *                                       they were embedded
     */
    public function __construct(protected array $samples) {}

    public function system(): string
    {
        return <<<'TEXT'
        You are labelling groups of job candidates for an international school's
        HR team, who hire across every role in the school — teaching and
        leadership, but also nursing, IT, finance, admin, catering, cleaning,
        maintenance, security and driving.

        You are shown several people who came out of the same group. Name what
        they have in common, as a short label a recruiter would recognise — a role
        family, a discipline, a level of seniority. Two to four words. Then one
        sentence saying who belongs in this group.

        Describe the group as it is. Do not judge its quality, rank it against
        other groups, or comment on whether these are good candidates.
        TEXT;
    }

    public function user(): string
    {
        $members = collect($this->samples)
            ->take(12)
            ->map(fn (string $s, int $i) => '### Person '.($i + 1)."\n".trim($s))
            ->implode("\n\n");

        return 'Name the group these people form.'
            .$this->section('The school', $this->school())
            .$this->section('Members', $members);
    }

    /**
     * @return array<string, string>
     */
    public function schema(): array
    {
        return ['name' => 'string', 'description' => 'string'];
    }
}
