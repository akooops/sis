<?php

namespace App\Ai\Prompts;

use App\Ai\Prompt;
use App\Models\Candidate;
use App\Models\JobOffer;

/**
 * How well one candidate fits one posting, 0-100, with the reasoning that
 * produced it.
 *
 * THE COMMENT IS NOT DECORATION. A shortlisting decision has to be explainable
 * to the person who has to defend it, and a bare number cannot be argued with or
 * corrected. It is also what makes a wrong score visible: a plausible number
 * with obviously mistaken reasoning is caught, a plausible number alone is not.
 */
class ScoreCandidateForOffer extends Prompt
{
    public function __construct(
        protected Candidate $candidate,
        protected JobOffer $offer,
        protected string $locale = 'en',
    ) {}

    public function system(): string
    {
        return <<<'TEXT'
        You are screening job applications for an international school's HR team.

        The school employs far more than teachers: leadership and admin staff,
        nurses and counsellors, IT and finance, drivers, security, catering,
        cleaning and maintenance. WORK OUT WHICH KIND OF ROLE THIS POSTING IS
        BEFORE YOU JUDGE ANYTHING, and apply the standards that belong to it.
        Marking a cleaner down for having no IB experience, or a teacher up for
        holding a driving licence, is a wrong answer.

        Score how well the candidate fits THIS posting, from 0 to 100, and explain
        the score in two or three sentences a hiring manager can act on. Name the
        strongest reason for the score and the biggest reservation. If something
        important is missing from the application, say that rather than assuming
        the worst or the best of it — a short application for a manual role is
        normal and is not itself a weakness.

        Judge the fit. Do not judge the person.
        TEXT;
    }

    public function user(): string
    {
        return 'Score this candidate against this posting.'
            .$this->section('The school', $this->school().' '.$this->market())
            .$this->section('The posting', $this->posting())
            .$this->section('The candidate', $this->profile());
    }

    /**
     * What actually predicts a hire in this market, stated once.
     *
     * WORK AUTHORISATION, NOT NATIONALITY. The obvious shortcut — rank Saudi
     * first, then the region, then everyone else — scores worse, because it is a
     * proxy for two things that can be looked at directly: whether the role
     * carries a Saudization quota, and whether the candidate can start without a
     * new visa. A Riyadh-resident teacher already holding a transferable permit
     * is a stronger hire than someone nearer by who needs fresh sponsorship, and
     * a nationality ranking gets that backwards.
     *
     * Saudization is genuinely load-bearing here: quotas are profession-specific
     * across hundreds of roles, so a school can be compliant overall and in
     * breach in one department, and losing compliance costs the right to sponsor
     * new visas at all. That makes a Saudi national's regulatory value LARGE for
     * administrative and support roles and modest for teaching ones, where expat
     * hiring is normal and sponsored.
     */
    protected function market(): string
    {
        return <<<'TEXT'
        These apply to EVERY role:

        - Can they actually do this job? Relevant experience counts for more than
          anything else, and relevant means relevant to THIS posting.
        - Continuity. Unexplained gaps in employment are checked closely in this
          market — note them as a question to ask, not as a disqualification.
        - Work authorisation. Someone already living in Saudi Arabia who can
          transfer their residency starts sooner and needs no new visa quota.
          Judge on residency and the right to work, NOT on nationality — with one
          exception below.
        - Saudization. Quotas are set per profession, and support, administrative
          and operational roles carry far heavier ones than teaching does; some are
          fully reserved for Saudi nationals. Where the posting looks like such a
          role, a Saudi national is genuinely more valuable and you should SAY SO
          in the comment rather than folding it silently into the number. For a
          teaching post, expatriate hiring is normal and sponsored, so nationality
          should barely move the score.

        Then apply whichever of these fits the posting:

        TEACHING AND ACADEMIC — subject knowledge; experience of the curriculum
        this school teaches (IB, British or American), which is worth much more
        than the same years elsewhere; a degree in the subject or in education;
        teaching registration or licensure at home; classroom experience with the
        age group. Arabic matters for some subjects and pastoral roles.

        LEADERSHIP AND ADMINISTRATION — experience running a team or a function in
        a school or a comparable organisation; the specific discipline (finance,
        admissions, HR, operations); written and spoken Arabic and English, since
        these roles deal with parents and with government paperwork.

        PROFESSIONAL AND TECHNICAL (nursing, counselling, IT, laboratory) — the
        licence or certification the role legally requires comes first; then
        experience in a school or with children where the role involves them.

        SUPPORT AND OPERATIONS (cleaning, maintenance, catering, security,
        drivers, groundskeeping) — judge on practical experience doing this work,
        reliability and length of service in previous posts, and any licence or
        certificate the job actually needs, such as a driving licence, a food
        handling certificate or a security permit. FORMAL EDUCATION IS LARGELY
        IRRELEVANT HERE and its absence must not reduce the score. Enough Arabic
        or English to follow safety instructions and work with colleagues matters;
        fluency usually does not. Comfort working on a school site around children
        is worth noting where the application says anything about it.
        TEXT;
    }

    protected function posting(): string
    {
        $skills = JobOffer::splitSkills($this->offer->getTranslation('skills', $this->locale, true));

        $lines = array_filter([
            'Title: '.($this->offer->getTranslation('title', $this->locale, true) ?: $this->offer->name),
            'Employment type: '.$this->offer->employment_type,
            'Work mode: '.$this->offer->work_mode,
            $this->offer->education_level ? 'Education required: '.$this->offer->education_level : null,
            $this->offer->experience_years ? 'Experience required: '.$this->offer->experience_years.' years' : null,
            $skills ? 'Skills wanted: '.implode(', ', $skills) : null,
            'Description: '.strip_tags((string) $this->offer->getTranslation('description', $this->locale, true)),
            'Details: '.strip_tags((string) $this->offer->getTranslation('content', $this->locale, true)),
        ]);

        return implode("\n", $lines);
    }

    /**
     * The candidate as the model sees them.
     *
     * Built from the TYPED tables rather than the raw submission, so the same
     * facts are described the same way for every candidate — which is the whole
     * reason the projection exists. Two applications worded differently must not
     * score differently for that reason alone.
     */
    protected function profile(): string
    {
        $this->candidate->loadMissing(['educations', 'experiences', 'languages', 'skills', 'country']);

        /*
         * EMPTY PARTS ARE OMITTED, not printed as blanks. An applicant who gave
         * only an institution produced "— King Saud University (–)", which reads
         * as a broken record rather than a sparse one and invites the model to
         * treat it as a data problem instead of a thin application.
         */
        $education = $this->candidate->educations
            ->map(fn ($e) => $this->line(
                array_filter([trim("{$e->degree} {$e->field_of_study}"), $e->institution, $this->years($e->start_year, $e->end_year)]),
                $e->description,
            ))
            ->filter()
            ->implode("\n");

        $experience = $this->candidate->experiences
            ->map(fn ($e) => $this->line(
                array_filter([$e->job_title, $e->company_name, $this->years($e->start_year, $e->is_current ? 'present' : $e->end_year)]),
                $e->description,
            ))
            ->filter()
            ->implode("\n");

        $languages = $this->candidate->languages->map(fn ($l) => "{$l->name}: {$l->proficiency}")->implode(', ');

        return implode("\n", array_filter([
            'Nationality: '.($this->candidate->country?->name ?? 'not stated'),
            'Total experience: '.$this->candidate->yearsOfExperience().' years',
            $this->candidate->skills->isNotEmpty() ? 'Skills: '.$this->candidate->skills->pluck('name')->implode(', ') : null,
            $languages ? 'Languages: '.$languages : null,
        ]))
            .$this->section('Education', $education)
            .$this->section('Experience', $experience);
    }

    /**
     * One history entry: the parts that exist, joined, with any notes after.
     *
     * @param  array<int, string>  $parts
     */
    protected function line(array $parts, ?string $notes): string
    {
        $parts = array_values(array_filter(array_map('trim', $parts), 'strlen'));

        if ($parts === []) {
            return '';
        }

        return implode(' — ', $parts).(trim((string) $notes) !== '' ? '. '.trim($notes) : '');
    }

    /** "2012–2016", "since 2017", "2016" — or nothing when neither is known. */
    protected function years(mixed $from, mixed $to): string
    {
        $from = trim((string) $from);
        $to = trim((string) $to);

        return match (true) {
            $from !== '' && $to !== '' => "{$from}–{$to}",
            $from !== '' => "since {$from}",
            $to !== '' => "until {$to}",
            default => '',
        };
    }

    /**
     * @return array<string, string>
     */
    public function schema(): array
    {
        return ['score' => 'int', 'comment' => 'string'];
    }
}
