<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\CandidateEducation;
use App\Models\CandidateExperience;
use App\Models\CandidateLanguage;
use App\Models\CandidateSkill;
use App\Models\Category;
use App\Models\Country;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\Language;
use App\States\JobApplication\Called;
use App\States\JobApplication\Contacted;
use App\States\JobApplication\Hired;
use App\States\JobApplication\Received;
use App\States\JobApplication\Rejected;
use App\States\JobApplication\Shortlisted;
use App\States\JobOffer\Published;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * A DEV FIXTURE for the HR module: 120 candidates, 17 open postings and 30
 * applications, so clustering, the talent pools and job matching have something
 * real-shaped to run against.
 *
 *     php artisan db:seed --class=DemoHrSeeder
 *
 * A FIXTURE, NOT A SECOND OPINION ABOUT WHAT THE SCHOOL SHOULD CONTAIN. It runs
 * from the `local` branch of DatabaseSeeder only, is firstOrCreate throughout, and
 * nothing in the app resolves any of its slugs — every posting here is deletable
 * and renameable, unlike the one `is_system` general application JobOffersSeeder
 * ships.
 *
 * THE DESCRIPTIONS ARE THE POINT. EmbedCandidate::describe() builds the embedded
 * text out of the experience and education descriptions, so those are what decide
 * whether clustering works at all: two people in one family have to read as the
 * same KIND of work in different words, and two people in different families have
 * to share almost no vocabulary. Filler ("responsible for various duties") embeds
 * into mush, every centroid lands on top of every other, and the rebuild returns
 * one pool — which is the exact failure Vectors::seeds() was rewritten to avoid.
 * Hence a per-family vocabulary of titled ROLES, each carrying its own CV line,
 * rather than 120 hand-written blocks.
 *
 * ELEVEN FAMILIES, BECAUSE THE SCHOOL HIRES FAR MORE THAN TEACHERS. Teaching is
 * five of them; the other six are leadership, front office, student support, IT,
 * finance and HR, and operations. A fixture of teachers only would exercise an
 * eighth of a scorer that reasons explicitly about role families, Saudization
 * quotas and work authorisation.
 *
 * DETERMINISTIC: no rand(), no shuffle(), no unseeded faker. Every variation is
 * derived from the index, so a rebuild produces the same 120 people and the
 * clusters under test stay comparable across runs.
 *
 * NO EMBEDDINGS, NO CLUSTERS, NO MATCHES. Those are the queued AI jobs' output
 * against a real provider; `embedding` and `embedded_at` are left null so
 * EmbedCandidate has work to do.
 */
class DemoHrSeeder extends Seeder
{
    /**
     * Total career length per person, cycled by position within the family so each
     * one spans a first-year joiner and a twenty-year veteran rather than a dozen
     * people with the same CV.
     *
     * @var array<int, int>
     */
    protected const SPANS = [1, 2, 3, 4, 6, 8, 11, 14, 17, 21];

    /**
     * Application statuses, cycled by applicant. Weighted the way a real pipeline
     * is — most arrivals are untouched or turned down, few are hired.
     *
     * @var array<int, class-string>
     */
    protected const STATUSES = [
        Received::class, Received::class, Shortlisted::class, Received::class, Contacted::class,
        Rejected::class, Called::class, Shortlisted::class, Hired::class, Rejected::class,
    ];

    /**
     * Where in-country candidates live. Address is not embedded, but it is what
     * tells a reader (and the scorer) whether someone needs fresh sponsorship.
     *
     * @var array<int, string>
     */
    protected const DISTRICTS = [
        'Al Muruj, Riyadh', 'Al Malaz, Riyadh', 'Al Nakheel, Riyadh', 'Hittin, Riyadh',
        'Al Yasmin, Riyadh', 'Al Sulaimaniyah, Riyadh', 'Al Olaya, Riyadh', 'Al Rawabi, Riyadh',
        'Al Aqiq, Riyadh', 'Qurtubah, Riyadh', 'Al Wurud, Riyadh', 'Al Izdihar, Riyadh',
        'Al Narjis, Riyadh', 'Al Rabwah, Riyadh',
    ];

    /** Countries resolved by ISO code, memoised — 120 lookups over 19 codes. */
    protected array $countries = [];

    /** Emails already claimed by this run, so two similar names cannot collide. */
    protected array $emails = [];

    /** The locale posting copy is written in — see postings(). */
    protected string $locale = 'en';

    public function run(): void
    {
        $this->locale = Language::defaultCode();

        /*
         * EVERY WRITE HERE IS EVENT-FREE, and it has to be. Candidate, JobOffer and
         * JobApplication are all observed, so a plain 120-person loop would write
         * several hundred activity-log rows recording that a seeder ran, and
         * JobApplicationObserver would emit a notification per application on top.
         * CLAUDE.md's own precedent is CandidateMatch, CandidateCluster and
         * JobOfferCluster, left deliberately unobserved because they are written in
         * bulk by machines: the audit log records what a PERSON did.
         *
         * Safe for ULIDs — Model::performInsert() calls setUniqueIds() itself
         * rather than through the `creating` event, so ids are still generated.
         * NOT safe for spatie/model-states defaults, which ARE applied from a
         * `creating` hook, so every status below is written out explicitly.
         */
        Model::withoutEvents(function () {
            $this->seedPostings();
            $this->seedPeople();
        });
    }

    /* -----------------------------------------
     Postings
    ------------------------------------------*/

    protected function seedPostings(): void
    {
        $category = Category::default()?->id;
        $created = 0;

        foreach ($this->postings() as $definition) {
            $offer = JobOffer::firstOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'employment_type' => $definition['employment_type'],
                    'work_mode' => $definition['work_mode'],
                    'experience_years' => $definition['experience_years'],
                    'education_level' => $definition['education_level'],
                    'start_date' => now()->addDays($definition['starts_in'])->toDateString(),
                    // scopeOpen() wants null or a future deadline, and the listing
                    // is the thing being tested — so every one of these is open.
                    'deadline_at' => $definition['deadline_in'] === null
                        ? null
                        : now()->addDays($definition['deadline_in']),
                    'status' => Published::class,
                    'published_at' => now()->subDays($definition['posted_days_ago']),
                    // A fixture, so NOT is_system: that flag belongs to the one
                    // seeded general application whose slug the apply flow resolves.
                    'is_system' => false,
                    'category_id' => $category,
                    // DEFAULT LOCALE ONLY. title/description/content/skills are
                    // translatable columns, and the nine-locale rule that governs
                    // system pages and forms is about strings the APP resolves —
                    // this is dev scaffolding an admin will overwrite or delete.
                    'title' => [$this->locale => $definition['title']],
                    'description' => [$this->locale => $definition['description']],
                    'content' => [$this->locale => $definition['content']],
                    'address' => [$this->locale => $definition['address']],
                    // A ;;;-joined string per locale, never a PHP array.
                    'skills' => [$this->locale => JobOffer::joinSkills($definition['skills'])],
                ],
            );

            $created += $offer->wasRecentlyCreated ? 1 : 0;

            /*
             * REFRESHED ON EVERY RUN, unlike everything else here.
             *
             * A deadline is the one field on a fixture that ROTS. firstOrCreate
             * writes it once, relative to the day of seeding, and then never
             * touches it again — so a month later scopeOpen() has quietly dropped
             * most of these, /jobs has shrunk, and the temporary/contractor/remote
             * values disappear from the filters that were the whole reason for
             * spreading employment_type across them. Reseeding could not repair
             * it, because the row already exists.
             *
             * So the dates are pushed forward each run while every authored value
             * is left alone. `start_date` moves with it: a posting that opens
             * before it closes is the only coherent pair.
             */
            $offer->forceFill([
                'start_date' => now()->addDays($definition['starts_in'])->toDateString(),
                'deadline_at' => $definition['deadline_in'] === null
                    ? null
                    : now()->addDays($definition['deadline_in']),
                'published_at' => now()->subDays($definition['posted_days_ago']),
            ])->saveQuietly();
        }

        $total = count($this->postings());

        $this->command?->info("Postings: {$created} created, ".($total - $created).' refreshed.');
    }

    /* -----------------------------------------
     People
    ------------------------------------------*/

    protected function seedPeople(): void
    {
        $sequence = 0;
        $applications = 0;

        foreach ($this->families() as $family) {
            $created = 0;
            $skipped = 0;

            foreach ($family['people'] as $index => $entry) {
                $sequence++;

                [$candidate, $isNew] = $this->candidate($entry, $sequence);

                if ($isNew) {
                    $this->profile($candidate, $family, $index);
                    $created++;
                } else {
                    // Their children are left exactly as they are: a re-run must not
                    // stack a second set of educations onto a person who has some.
                    $skipped++;
                }

                $applications += $this->apply($candidate, $family, $sequence) ? 1 : 0;
            }

            $this->command?->info(sprintf(
                '  %-22s %2d created, %2d already present.',
                $family['label'].':',
                $created,
                $skipped,
            ));
        }

        $this->command?->info("Candidates: {$sequence} in the fixture, {$applications} with an application.");
    }

    /**
     * The person. IDENTITY IS THE EMAIL, which is what makes the whole fixture
     * re-runnable — Candidate is unique on it and the projector looks people up by
     * email or phone, so a stable address means one row per person forever.
     *
     * Takes only the name/nationality entry and the global sequence, deliberately:
     * a PERSON does not vary with their family or their position in it. Everything
     * that does is composed in profile().
     *
     * @param  array{0: string, 1: string, 2: string}  $entry
     * @return array{0: Candidate, 1: bool}
     */
    protected function candidate(array $entry, int $sequence): array
    {
        [$first, $last, $code] = $entry;

        $candidate = Candidate::firstOrCreate(
            ['email' => $this->email($first, $last)],
            [
                'first_name' => $first,
                'last_name' => $last,
                // ALREADY E164, the shape PhoneFormatter::e164() produces, because
                // ApplicationProjector matches on email OR phone and a differently
                // formatted number becomes a second person. All Saudi mobiles: the
                // shape is what carries meaning, the dialling code does not, and
                // one verified-valid pattern beats nineteen half-checked ones —
                // an applicant to a Riyadh school gives a local number anyway.
                'phone' => '+96650'.(1000000 + $sequence),
                'address' => $this->address($code, $sequence),
                'country_id' => $this->country($code),
                // Left null on purpose: embeddings are the queued AI jobs' output.
                'embedding' => null,
                'embedded_at' => null,
            ],
        );

        return [$candidate, $candidate->wasRecentlyCreated];
    }

    /**
     * Experience, education, languages and skills for one person.
     *
     * Composed from the family's vocabulary and the person's position in it, so
     * nothing is random and nothing is duplicated by hand.
     *
     * @param  array<string, mixed>  $family
     */
    protected function profile(Candidate $candidate, array $family, int $index): void
    {
        $roles = $this->roles($family, $index);

        // The earliest year they were in work, which experiences() alone knows —
        // it is the one that pushes older roles further back for an employment
        // gap. educations() needs it so nobody graduates after their first job.
        $earliest = $this->experiences($candidate, $roles, $index);

        $this->educations($candidate, $family, $roles[0], $index, $earliest);
        $this->languages($candidate, $family['people'][$index][2], $index);
        $this->skills($candidate, $family, $roles, $index);
    }

    /**
     * The roles this person has held, newest first.
     *
     * ROLE COUNT FOLLOWS CAREER LENGTH rather than the raw index: four jobs inside
     * a two-year career would read as nonsense to anyone reviewing the fixture, and
     * the scorer reads employment history as a signal.
     *
     * @param  array<string, mixed>  $family
     * @return array<int, array<string, mixed>>
     */
    protected function roles(array $family, int $index): array
    {
        $span = self::SPANS[$index % count(self::SPANS)];
        $count = min(1 + intdiv($span, 5), 4);
        $available = count($family['roles']);

        /*
         * START AND STRIDE BOTH VARY, and the stride is why.
         *
         * The first version was `($index * 3 + $j) % $available`. With nine roles
         * in a family, a stride of 3 shares a factor with 9 — so indices 0, 3, 6
         * and 9 all started at role 0, and any two people whose start AND role
         * count coincided got a word-for-word identical work history: same title,
         * same employer, same duty line. It was 14 pairs across 28 of the 120,
         * which is exactly what makes a fixture read as generated the moment two
         * candidate drawers are opened side by side.
         *
         * The start walks the list one person at a time; the stride changes each
         * time the start WRAPS, so the people who share a start still read in a
         * different order. 1-3 keeps the roles within one person distinct for
         * every family size here (all >= 8 roles, at most 4 held).
         */
        $start = $index % $available;
        $stride = 1 + intdiv($index, $available) % 3;

        $roles = [];

        for ($j = 0; $j < $count; $j++) {
            $roles[] = $family['roles'][($start + $j * $stride) % $available];
        }

        return $roles;
    }

    /**
     * @param  array<int, array<string, mixed>>  $roles
     * @return int the earliest year this person was in work
     */
    protected function experiences(Candidate $candidate, array $roles, int $index): int
    {
        $reference = (int) now()->year;
        $span = self::SPANS[$index % count(self::SPANS)];
        $count = count($roles);

        // 80% are in post. `is_current` is not the same answer as a missing end
        // year, and only the first counts toward yearsOfExperience().
        $current = ($index % 5) !== 3;

        $base = max(1, intdiv($span, $count));
        $lengths = array_fill(0, $count, $base);
        $lengths[$count - 1] = max(1, $span - $base * ($count - 1));

        $cursor = $reference;
        $earliest = $reference;

        foreach ($roles as $j => $role) {
            $start = $cursor - $lengths[$j];
            $earliest = min($earliest, $start);

            CandidateExperience::create([
                'candidate_id' => $candidate->id,
                'company_name' => $role['employer'],
                'job_title' => $role['title'],
                'start_year' => $start,
                'end_year' => ($j === 0 && $current) ? null : $cursor,
                'is_current' => $j === 0 && $current,
                'description' => $role['duty'],
                'order' => $j,
            ]);

            // A gap for one person in seven. Employment gaps are a real signal in
            // this market — the scorer is told to treat one as a question to ask,
            // and it needs examples to ask about.
            $cursor = $start - (($index % 7 === 0 && $j < $count - 1) ? 1 : 0);
        }

        return $earliest;
    }

    /**
     * Qualifications, highest and most recent first.
     *
     * COUNT FOLLOWS CAREER LENGTH again: three degrees behind a one-year career
     * would put someone in university at fifteen. A family may also cap it —
     * operations tops out at two, because for support and operational roles formal
     * education is largely irrelevant and its absence must not read as a gap.
     *
     * @param  array<string, mixed>  $family
     * @param  array<string, mixed>  $primary
     * @param  int  $earliest  the year their first job started — see below
     */
    protected function educations(Candidate $candidate, array $family, array $primary, int $index, int $earliest): void
    {
        $span = self::SPANS[$index % count(self::SPANS)];
        $count = min(1 + intdiv($span, 8), $family['max_educations']);

        $available = count($family['educations']);

        /*
         * ANCHORED ON THE FIRST JOB, not on `now - span`.
         *
         * Those two agree for most people and diverge for the one in seven who
         * carries an employment gap: experiences() pushes their older roles a year
         * further back per gap, so a career computed independently here started
         * two years BEFORE the newest degree finished — eleven candidates holding
         * a graduate post before graduating, in a fixture whose whole point is
         * that a reviewer can read it without wincing.
         */
        $end = $earliest;

        for ($k = 0; $k < $count; $k++) {
            // The newest qualification is the one the current role implies, where
            // the role declares one — a HVAC technician's trade certificate rather
            // than whatever the family list happens to hold at that index.
            $education = ($k === 0 && isset($primary['education']))
                ? $primary['education']
                : $family['educations'][($index + $k) % $available];

            CandidateEducation::create([
                'candidate_id' => $candidate->id,
                'institution' => $education['institution'],
                'degree' => $education['degree'],
                'field_of_study' => $education['field'],
                'start_year' => $end - $education['years'],
                'end_year' => $end,
                'description' => $education['description'],
                'order' => $k,
            ]);

            // Each earlier qualification sits four years further back.
            $end -= 4;
        }
    }

    /**
     * Languages, from nationality rather than from the family.
     *
     * Nationality is deliberately absent from the embedded text — it says nothing
     * about what someone can do — but the languages someone actually speaks do
     * carry signal, and they follow from where they are from.
     */
    protected function languages(Candidate $candidate, string $code, int $index): void
    {
        $spoken = $this->spokenLanguages()[$code];
        $count = min(1 + ($index % 3), count($spoken));

        for ($k = 0; $k < $count; $k++) {
            CandidateLanguage::create([
                'candidate_id' => $candidate->id,
                'name' => $spoken[$k][0],
                'proficiency' => $spoken[$k][1],
                'order' => $k,
            ]);
        }
    }

    /**
     * Three to eight skills: the roles' own first, topped up from the family's
     * general pool.
     *
     * FOLDED AND DEDUPED, because candidate_skills is unique on
     * (candidate_id, fold) — two skills folding the same would throw rather than
     * being ignored, which is the same guard ApplicationProjector keeps.
     *
     * @param  array<string, mixed>  $family
     * @param  array<int, array<string, mixed>>  $roles
     */
    protected function skills(Candidate $candidate, array $family, array $roles, int $index): void
    {
        $target = 3 + ($index % 6);
        $pool = [];

        foreach ($roles as $role) {
            foreach ($role['skills'] as $name) {
                $pool[] = $name;
            }
        }

        $common = $family['skills'];
        $size = count($common);

        for ($k = 0; $k < $size; $k++) {
            $pool[] = $common[($index * 5 + $k) % $size];
        }

        $chosen = [];

        foreach ($pool as $name) {
            $fold = CandidateSkill::fold($name);

            if (isset($chosen[$fold]) || count($chosen) >= $target) {
                continue;
            }

            $chosen[$fold] = $name;
        }

        foreach ($chosen as $fold => $name) {
            CandidateSkill::create([
                'candidate_id' => $candidate->id,
                'name' => $name,
                'fold' => $fold,
            ]);
        }
    }

    /**
     * One application in four candidates, against a posting.
     *
     * Mostly the family's own posting, so a per-posting candidate list is coherent;
     * every seventh applicant goes to the general application and every seventh
     * after that to a deliberately poor fit, because matching is only testable
     * when there is something implausible to score as well as something plausible.
     *
     * @param  array<string, mixed>  $family
     */
    protected function apply(Candidate $candidate, array $family, int $sequence): bool
    {
        if ($sequence % 4 !== 1) {
            return false;
        }

        $ordinal = intdiv($sequence, 4);

        /*
         * ONE IN THREE IS A POOR FIT, not one in seven.
         *
         * The first cut only routed `$ordinal % 7 === 5` to the family's
         * deliberately-wrong posting — and since applications are minted at
         * `$sequence % 4 === 1`, only four of the eleven families ever produced
         * such an ordinal. Seven `posting_alt` declarations were dead config, five
         * postings had no applicants at all, and 4 poor fits out of 30 barely
         * exercised the thing the alt exists for: a scorer is only demonstrably
         * working if it marks a bad pairing DOWN, and a fixture of near-perfect
         * matches cannot show that.
         */
        $slug = match ($ordinal % 3) {
            1 => $family['posting_alt'],
            2 => $ordinal % 6 === 2 ? JobOffer::GENERAL_SLUG : $family['posting'],
            default => $family['posting'],
        };

        $offer = JobOffer::query()->where('slug', $slug)->first();

        if (! $offer) {
            return false;
        }

        // firstOrCreate on the pair, which is also the unique index — one person
        // applies to one posting once, and a re-run finds the row rather than
        // colliding with it.
        JobApplication::firstOrCreate(
            ['job_offer_id' => $offer->id, 'candidate_id' => $candidate->id],
            [
                'status' => self::STATUSES[$ordinal % count(self::STATUSES)],
                'applied_at' => now()->subDays(3 + ($sequence % 45)),
                // No form_submission_id: nobody filled a form in. The projector is
                // what links the two, and this fixture bypasses it by design.
                'form_submission_id' => null,
            ],
        );

        return true;
    }

    /* -----------------------------------------
     Helpers
    ------------------------------------------*/

    /** A stable, unique, obviously-fake address. RFC 2606 reserves example.com. */
    protected function email(string $first, string $last): string
    {
        $base = Str::slug($first, '.').'.'.Str::slug($last, '.');
        $email = $base.'@example.com';
        $suffix = 2;

        while (isset($this->emails[$email])) {
            $email = $base.$suffix++.'@example.com';
        }

        $this->emails[$email] = true;

        return $email;
    }

    /**
     * BY ISO CODE, never by a guessed id — countries are seeded with ULIDs and all
     * 249 rows exist. Memoised: 120 people over nineteen nationalities.
     */
    protected function country(string $code): ?string
    {
        if (! array_key_exists($code, $this->countries)) {
            $this->countries[$code] = Country::query()->where('code', $code)->value('id');
        }

        return $this->countries[$code];
    }

    /**
     * One applicant in six is writing from abroad and would need sponsorship; the
     * rest are already in Riyadh. That difference is the scorer's real question
     * about work authorisation, so the fixture has to contain both.
     */
    protected function address(string $code, int $sequence): string
    {
        if ($sequence % 6 === 5) {
            return $this->originCities()[$code];
        }

        return self::DISTRICTS[$sequence % count(self::DISTRICTS)];
    }

    /**
     * @return array<string, array<int, array{0: string, 1: string}>>
     */
    protected function spokenLanguages(): array
    {
        return [
            'SA' => [['Arabic', 'native'], ['English', 'advanced']],
            'EG' => [['Arabic', 'native'], ['English', 'advanced']],
            'JO' => [['Arabic', 'native'], ['English', 'advanced']],
            'SY' => [['Arabic', 'native'], ['English', 'intermediate']],
            'LB' => [['Arabic', 'native'], ['French', 'advanced'], ['English', 'advanced']],
            'SD' => [['Arabic', 'native'], ['English', 'intermediate']],
            'MA' => [['Arabic', 'native'], ['French', 'advanced'], ['English', 'intermediate']],
            'YE' => [['Arabic', 'native'], ['English', 'basic']],
            'IN' => [['Hindi', 'native'], ['English', 'advanced'], ['Arabic', 'basic']],
            'PK' => [['Urdu', 'native'], ['English', 'advanced'], ['Arabic', 'basic']],
            'PH' => [['Filipino', 'native'], ['English', 'advanced'], ['Arabic', 'basic']],
            'BD' => [['Bengali', 'native'], ['English', 'intermediate'], ['Arabic', 'basic']],
            'NP' => [['Nepali', 'native'], ['Hindi', 'advanced'], ['English', 'intermediate']],
            'GB' => [['English', 'native'], ['Arabic', 'basic']],
            'US' => [['English', 'native'], ['Spanish', 'intermediate']],
            'CA' => [['English', 'native'], ['French', 'advanced']],
            'IE' => [['English', 'native'], ['Irish', 'intermediate']],
            'ZA' => [['English', 'native'], ['Afrikaans', 'advanced'], ['isiZulu', 'basic']],
            'AU' => [['English', 'native'], ['Arabic', 'basic']],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function originCities(): array
    {
        return [
            'SA' => 'Al Khobar, Saudi Arabia',
            'EG' => 'Nasr City, Cairo, Egypt',
            'JO' => 'Amman, Jordan',
            'SY' => 'Damascus, Syria',
            'LB' => 'Beirut, Lebanon',
            'SD' => 'Khartoum, Sudan',
            'MA' => 'Casablanca, Morocco',
            'YE' => 'Sanaa, Yemen',
            'IN' => 'Kochi, Kerala, India',
            'PK' => 'Lahore, Pakistan',
            'PH' => 'Quezon City, Philippines',
            'BD' => 'Dhaka, Bangladesh',
            'NP' => 'Kathmandu, Nepal',
            'GB' => 'Manchester, United Kingdom',
            'US' => 'Austin, Texas, United States',
            'CA' => 'Mississauga, Ontario, Canada',
            'IE' => 'Cork, Ireland',
            'ZA' => 'Durban, South Africa',
            'AU' => 'Brisbane, Australia',
        ];
    }

    /* -----------------------------------------
     The postings
    ------------------------------------------*/

    /**
     * Seventeen open vacancies across the same eleven families as the candidates,
     * so every applicant has both a plausible and an implausible target.
     *
     * EMPLOYMENT TYPE AND WORK MODE ARE SPREAD DELIBERATELY. The public listing
     * filters on both, so the fixture is also what makes those filters testable:
     * full_time, part_time, contractor, temporary, intern, per_diem and volunteer
     * all appear, and `other` is already covered by the seeded general
     * application. All three work modes appear.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function postings(): array
    {
        $riyadh = 'Saud International Schools, Al Muruj, Riyadh 12281, Saudi Arabia';

        return [
            [
                'slug' => 'primary-class-teacher-year-4',
                'name' => 'Primary Class Teacher (Year 4)',
                'title' => 'Primary Class Teacher — Year 4',
                'description' => 'A class teacher for Year 4, teaching the full primary curriculum to a mixed-ability class of 24.',
                'content' => '<p>We are looking for a primary class teacher to take a Year 4 class from August. You will teach English, mathematics, science and topic work, plan across a two-week cycle with the phase team, and report to parents three times a year.</p><ul><li>QTS, PGCE or an equivalent primary teaching qualification</li><li>Experience of the English National Curriculum or the IB Primary Years Programme</li><li>Confident with synthetic phonics and guided reading</li><li>Willing to run one after-school club a week</li></ul>',
                'employment_type' => 'full_time',
                'work_mode' => 'onsite',
                'experience_years' => 2,
                'education_level' => 'bachelor',
                'starts_in' => 120,
                'deadline_in' => 45,
                'posted_days_ago' => 12,
                'address' => $riyadh,
                'skills' => ['Classroom management', 'Phonics', 'Guided reading', 'Differentiation', 'English National Curriculum', 'Formative assessment'],
            ],
            [
                'slug' => 'secondary-mathematics-teacher',
                'name' => 'Secondary Mathematics Teacher',
                'title' => 'Secondary Mathematics Teacher (IGCSE and A-Level)',
                'description' => 'Teach mathematics across Years 7 to 13, including IGCSE and A-Level sets.',
                'content' => '<p>A mathematics specialist to teach across the secondary phase, with two A-Level sets and responsibility for one Key Stage 3 scheme of work. The department is eleven strong across two campuses.</p><ul><li>A mathematics or closely related degree</li><li>Proven IGCSE results at grade 5 and above</li><li>A-Level or IB Diploma experience is an advantage</li><li>Comfortable with GeoGebra and Desmos in lessons</li></ul>',
                'employment_type' => 'full_time',
                'work_mode' => 'onsite',
                'experience_years' => 3,
                'education_level' => 'bachelor',
                'starts_in' => 120,
                'deadline_in' => 60,
                'posted_days_ago' => 20,
                'address' => $riyadh,
                'skills' => ['IGCSE Mathematics', 'A-Level teaching', 'Scheme of work design', 'GeoGebra', 'Data-driven intervention'],
            ],
            [
                'slug' => 'secondary-english-teacher',
                'name' => 'Secondary English Teacher',
                'title' => 'Secondary English Teacher (IGCSE)',
                'description' => 'Teach English language and literature to Years 7 to 11, first and second language candidates.',
                'content' => '<p>An English teacher for the secondary phase, teaching the IGCSE poetry anthology and coursework portfolio to both first-language and second-language candidates, and running the Key Stage 4 intervention group.</p><ul><li>A degree in English or a closely related subject</li><li>IGCSE English Language and Literature experience</li><li>Experience teaching students for whom English is an additional language</li></ul>',
                'employment_type' => 'full_time',
                'work_mode' => 'onsite',
                'experience_years' => 2,
                'education_level' => 'bachelor',
                'starts_in' => 120,
                // No deadline: this one is open until it is filled, which is also
                // the null branch of scopeOpen().
                'deadline_in' => null,
                'posted_days_ago' => 30,
                'address' => $riyadh,
                'skills' => ['IGCSE English', 'Essay feedback', 'Reading-age tracking', 'EAL language plans', 'Coursework moderation'],
            ],
            [
                'slug' => 'arabic-language-teacher-primary',
                'name' => 'Arabic Language Teacher (Primary)',
                'title' => 'Arabic Language Teacher — Primary Phase',
                'description' => 'Teach the Ministry of Education Arabic curriculum to Grades 3 to 6.',
                'content' => '<p>An Arabic teacher for the primary phase, teaching grammar, dictation and composition to native speakers and running the Arabic support group for returning expatriate pupils.</p><ul><li>A degree in Arabic language and literature or Islamic studies</li><li>Familiarity with the Ministry of Education curriculum and its assessments</li><li>Experience of teaching Arabic as a second language is welcome</li></ul>',
                'employment_type' => 'full_time',
                'work_mode' => 'onsite',
                'experience_years' => 2,
                'education_level' => 'bachelor',
                'starts_in' => 120,
                'deadline_in' => 30,
                'posted_days_ago' => 8,
                'address' => $riyadh,
                'skills' => ['Arabic grammar (nahw)', 'Arabic composition', 'Ministry of Education curriculum', 'Handwriting and dictation'],
            ],
            [
                'slug' => 'kindergarten-homeroom-teacher',
                'name' => 'Kindergarten Homeroom Teacher (KG2)',
                'title' => 'Kindergarten Homeroom Teacher — KG2',
                'description' => 'A KG2 homeroom teacher for a class of 20 five-year-olds, working with two assistants.',
                'content' => '<p>An early years practitioner to lead a KG2 class through early phonics, number recognition and structured play, keeping individual learning journals and leading termly parent consultations.</p><ul><li>An early childhood qualification, degree or Level 3 diploma</li><li>Experience of the Early Years Foundation Stage or an equivalent framework</li><li>Patience, energy and a good singing voice</li></ul>',
                'employment_type' => 'full_time',
                'work_mode' => 'onsite',
                'experience_years' => 1,
                'education_level' => 'associate',
                'starts_in' => 120,
                'deadline_in' => 40,
                'posted_days_ago' => 15,
                'address' => $riyadh,
                'skills' => ['Early Years Foundation Stage', 'Play-based learning', 'Learning journals', 'Early phonics', 'Safeguarding'],
            ],
            [
                'slug' => 'head-of-science',
                'name' => 'Head of Science',
                'title' => 'Head of Science',
                'description' => 'Lead a science department of nine across biology, chemistry and physics, Years 7 to 13.',
                'content' => '<p>A middle leadership post with a two-thirds teaching load. You will own the assessment calendar, run lesson observations and appraisal for nine staff, and manage the laboratory technician, stock and risk assessment records.</p><ul><li>Substantial secondary science teaching at IGCSE and A-Level or IB Diploma</li><li>Prior experience as a head of department or a second in department</li><li>A postgraduate qualification in education or leadership</li></ul>',
                'employment_type' => 'full_time',
                'work_mode' => 'onsite',
                'experience_years' => 8,
                'education_level' => 'postgraduate',
                'starts_in' => 120,
                'deadline_in' => 75,
                'posted_days_ago' => 25,
                'address' => $riyadh,
                'skills' => ['Department leadership', 'Lesson observation', 'Appraisal and performance management', 'Laboratory management', 'Risk assessment'],
            ],
            [
                'slug' => 'school-registrar',
                'name' => 'School Registrar',
                'title' => 'School Registrar',
                'description' => 'Own admissions and student records for a 1,400-pupil school, enquiry through to enrolment.',
                'content' => '<p>The registrar owns the student record: admissions enquiries, entrance assessment scheduling, enrolment contracts, transfer certificates and the Ministry census return. You will work in Arabic and English every day.</p><ul><li>Five years in school administration or a records-heavy office role</li><li>PowerSchool, or a comparable student information system</li><li>The Ministry of Education Noor system</li><li>Fluent written Arabic and English</li></ul>',
                'employment_type' => 'full_time',
                'work_mode' => 'onsite',
                'experience_years' => 5,
                'education_level' => 'bachelor',
                'starts_in' => 45,
                'deadline_in' => 25,
                'posted_days_ago' => 10,
                'address' => $riyadh,
                'skills' => ['PowerSchool', 'Ministry of Education Noor system', 'Admissions administration', 'Records management', 'Arabic and English correspondence'],
            ],
            [
                'slug' => 'school-nurse',
                'name' => 'School Nurse',
                'title' => 'School Nurse',
                'description' => 'Run the health room for 1,200 pupils, 25 to 40 presentations a day.',
                'content' => '<p>A registered nurse to run the health room single-handed: daily presentations, the medication register, anaphylaxis and asthma care plans, immunisation records and annual screening.</p><ul><li>Registration with the Saudi Commission for Health Specialties</li><li>Paediatric experience, hospital or school</li><li>Current paediatric first aid and basic life support</li></ul>',
                'employment_type' => 'full_time',
                'work_mode' => 'onsite',
                'experience_years' => 3,
                'education_level' => 'bachelor',
                'starts_in' => 60,
                'deadline_in' => 35,
                'posted_days_ago' => 18,
                'address' => $riyadh,
                'skills' => ['Paediatric first aid', 'Medication administration', 'Immunisation records', 'Care plan writing', 'Saudi Commission for Health Specialties registration'],
            ],
            [
                'slug' => 'it-support-technician',
                'name' => 'IT Support Technician',
                'title' => 'IT Support Technician',
                'description' => 'First and second line support for 900 devices across three campuses.',
                'content' => '<p>Hands-on support for staff and student devices: Windows laptops, iPads, interactive panels and the print estate, against a same-day ticket target. One day a week may be worked remotely once you are settled.</p><ul><li>Two years of helpdesk or desktop support</li><li>Active Directory and Microsoft 365 administration</li><li>Basic networking — switches, VLANs, wireless</li><li>CompTIA A+ or equivalent is welcome</li></ul>',
                'employment_type' => 'full_time',
                'work_mode' => 'hybrid',
                'experience_years' => 2,
                'education_level' => 'associate',
                'starts_in' => 30,
                'deadline_in' => 20,
                'posted_days_ago' => 6,
                'address' => $riyadh,
                'skills' => ['Active Directory', 'Microsoft 365 administration', 'Helpdesk ticketing', 'Interactive panel support', 'VLAN configuration'],
            ],
            [
                'slug' => 'payroll-and-hr-officer',
                'name' => 'Payroll and HR Officer',
                'title' => 'Payroll and HR Officer (Part Time)',
                'description' => 'Payroll for 210 staff and the employee lifecycle, three days a week.',
                'content' => '<p>A part-time officer to run monthly payroll through the WPS, GOSI contributions and end-of-service accruals, alongside contracts, leave records and Qiwa and Mudad registration. Two days on site, one from home.</p><ul><li>Saudi payroll experience, including GOSI and end-of-service benefits</li><li>Qiwa, Mudad, Absher and Muqeem</li><li>An eye for Saudization banding and its reporting</li></ul>',
                'employment_type' => 'part_time',
                'work_mode' => 'hybrid',
                'experience_years' => 4,
                'education_level' => 'bachelor',
                'starts_in' => 30,
                'deadline_in' => 50,
                'posted_days_ago' => 22,
                'address' => $riyadh,
                'skills' => ['Payroll (WPS)', 'GOSI', 'End-of-service benefits', 'Qiwa and Mudad', 'Saudization compliance'],
            ],
            [
                'slug' => 'online-mathematics-tutor',
                'name' => 'Online Mathematics Tutor',
                'title' => 'Online Mathematics Tutor (Evenings)',
                'description' => 'Evening one-to-one and small-group tutoring for IGCSE mathematics candidates, fully remote.',
                'content' => '<p>Two to three evenings a week, remote, tutoring borderline IGCSE candidates in small groups. Sessions run on the school platform; you will report progress fortnightly to the head of mathematics.</p><ul><li>Secondary mathematics teaching or tutoring experience</li><li>A reliable connection and a quiet space</li><li>Comfortable teaching over video with a shared whiteboard</li></ul>',
                'employment_type' => 'part_time',
                'work_mode' => 'remote',
                'experience_years' => 1,
                'education_level' => 'bachelor',
                'starts_in' => 20,
                'deadline_in' => 28,
                'posted_days_ago' => 4,
                'address' => 'Remote',
                'skills' => ['IGCSE Mathematics', 'Data-driven intervention', 'Formative assessment'],
            ],
            [
                'slug' => 'school-bus-driver',
                'name' => 'School Bus Driver',
                'title' => 'School Bus Driver',
                'description' => 'A fixed Riyadh route twice a day, 42 pupils per run.',
                'content' => '<p>Two runs a day on a fixed route, with pre-trip checks, the fuel log and a monthly defect report. Between runs you will support the transport office. A clean record and a calm manner with children matter more than anything on paper.</p><ul><li>A valid Saudi heavy vehicle licence</li><li>Three years of passenger driving, school transport preferred</li><li>No reportable incidents</li></ul>',
                'employment_type' => 'full_time',
                'work_mode' => 'onsite',
                'experience_years' => 3,
                'education_level' => 'high_school',
                'starts_in' => 21,
                'deadline_in' => 15,
                'posted_days_ago' => 5,
                'address' => $riyadh,
                'skills' => ['Saudi heavy vehicle licence', 'Defensive driving', 'Pre-trip vehicle checks', 'First aid'],
            ],
            [
                'slug' => 'hvac-technician',
                'name' => 'HVAC Technician',
                'title' => 'HVAC Technician (Contract)',
                'description' => 'Twelve-month contract servicing 60 split units and two chiller plants.',
                'content' => '<p>A twelve-month contract covering planned and reactive work on the cooling estate: filter changes, gas top-up, coil cleaning and the quarterly schedule, logged on the CAFM system.</p><ul><li>A trade certificate in refrigeration and air conditioning</li><li>Five years on commercial split units and chillers</li><li>Comfortable working to a permit-to-work system</li></ul>',
                'employment_type' => 'contractor',
                'work_mode' => 'onsite',
                'experience_years' => 5,
                'education_level' => 'professional_certificate',
                'starts_in' => 14,
                'deadline_in' => 22,
                'posted_days_ago' => 9,
                'address' => $riyadh,
                'skills' => ['Split unit servicing', 'Planned preventive maintenance', 'CAFM work orders', 'Permit to work'],
            ],
            [
                'slug' => 'examination-invigilator',
                'name' => 'Examination Invigilator',
                'title' => 'Examination Invigilator (May and June session)',
                'description' => 'Temporary invigilation cover for the May and June examination session.',
                'content' => '<p>Sessional work across six weeks: setting out halls, reading the regulations, invigilating to exam board rules and completing the seating and incident paperwork. Training is provided.</p><ul><li>Punctual, calm and precise</li><li>No teaching qualification needed</li><li>Available for morning and afternoon sessions across the whole window</li></ul>',
                'employment_type' => 'temporary',
                'work_mode' => 'onsite',
                'experience_years' => null,
                'education_level' => 'high_school',
                'starts_in' => 90,
                'deadline_in' => 18,
                'posted_days_ago' => 3,
                'address' => $riyadh,
                'skills' => ['Time keeping', 'Records management', 'Safeguarding'],
            ],
            [
                'slug' => 'teaching-internship-primary',
                'name' => 'Teaching Internship (Primary)',
                'title' => 'Teaching Internship — Primary Phase',
                'description' => 'A paid one-year internship alongside a mentor class teacher in the primary phase.',
                'content' => '<p>For a recent education graduate: a year in a Year 3 or Year 4 classroom alongside an experienced mentor, with a structured programme of observation, co-teaching and reflection, and release time for study.</p><ul><li>An education degree completed within the last two years</li><li>No classroom experience required beyond your placements</li><li>A genuine intention to teach</li></ul>',
                'employment_type' => 'intern',
                'work_mode' => 'onsite',
                'experience_years' => null,
                'education_level' => 'bachelor',
                'starts_in' => 120,
                'deadline_in' => 55,
                'posted_days_ago' => 14,
                'address' => $riyadh,
                'skills' => ['Lesson planning', 'Classroom management', 'Differentiation'],
            ],
            [
                'slug' => 'supply-teacher-pool',
                'name' => 'Supply Teacher Pool',
                'title' => 'Supply Teacher Pool (Daily Rate)',
                'description' => 'Join the day-rate cover pool across primary and secondary, called as needed.',
                'content' => '<p>We keep a pool of vetted supply teachers called by the cover manager the evening before or on the morning. You choose which days you accept; we ask for a minimum of four days a month.</p><ul><li>A recognised teaching qualification in any phase</li><li>Valid transferable residency, or Saudi nationality</li><li>Willing to take any subject at Key Stage 3</li></ul>',
                'employment_type' => 'per_diem',
                'work_mode' => 'onsite',
                'experience_years' => 1,
                'education_level' => 'bachelor',
                'starts_in' => 10,
                'deadline_in' => null,
                'posted_days_ago' => 40,
                'address' => $riyadh,
                'skills' => ['Classroom management', 'Behaviour management', 'Lesson planning'],
            ],
            [
                'slug' => 'reading-volunteer-primary-library',
                'name' => 'Reading Volunteer (Primary Library)',
                'title' => 'Reading Volunteer — Primary Library',
                'description' => 'Hear readers one morning a week in the primary library. Unpaid.',
                'content' => '<p>Volunteers hear four or five children read for twenty minutes each, one morning a week, following the reading record the class teacher keeps. Safeguarding checks and a short induction apply.</p><ul><li>Patience and warmth with young children</li><li>Fluent spoken English or Arabic</li><li>The same morning every week during term</li></ul>',
                'employment_type' => 'volunteer',
                'work_mode' => 'onsite',
                'experience_years' => null,
                'education_level' => null,
                'starts_in' => 30,
                'deadline_in' => 70,
                'posted_days_ago' => 2,
                'address' => $riyadh,
                'skills' => ['Guided reading', 'Safeguarding'],
            ],
        ];
    }

    /* -----------------------------------------
     The families
    ------------------------------------------*/

    /**
     * Eleven role families, each carrying its own vocabulary.
     *
     * `people` is the only per-person data: name and nationality. Everything else
     * is composed from `roles`, `educations` and `skills` by index, which is what
     * keeps 120 people out of 120 hand-written blocks.
     *
     * A ROLE IS A TITLE PLUS ITS OWN CV LINE, not a title drawn from one list and a
     * duty from another — otherwise a bus driver ends up describing chiller
     * servicing, and the embedding is being fed a person who does not exist.
     *
     * NATIONALITY IS WEIGHTED THE WAY THE RIYADH MARKET ACTUALLY IS: teaching skews
     * to the UK, Ireland, South Africa, Egypt and Jordan; operations and the front
     * office skew to South Asia and the Levant; Saudi nationals appear everywhere
     * and are heaviest in admin, finance and operations, which carry the heavier
     * Saudization quotas the scorer reasons about.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function families(): array
    {
        return [
            $this->primaryTeachers(),
            $this->stemTeachers(),
            $this->humanitiesTeachers(),
            $this->arabicTeachers(),
            $this->earlyYears(),
            $this->leadership(),
            $this->frontOffice(),
            $this->studentSupport(),
            $this->technology(),
            $this->financeAndHr(),
            $this->operations(),
        ];
    }

    /** @return array<string, mixed> */
    protected function primaryTeachers(): array
    {
        return [
            'label' => 'Primary teaching',
            'posting' => 'primary-class-teacher-year-4',
            'posting_alt' => 'hvac-technician',
            'max_educations' => 3,
            'people' => [
                ['Emily', 'Whitfield', 'GB'],
                ['Ciara', 'Donnelly', 'IE'],
                ['Thandi', 'Mokoena', 'ZA'],
                ['Nourhan', 'Ibrahim', 'EG'],
                ['Rania', 'Al Khatib', 'JO'],
                ['Daniel', 'Ashcroft', 'GB'],
                ['Pieter', 'van Wyk', 'ZA'],
                ['Mostafa', 'Selim', 'EG'],
                ['Reem', 'Al Otaibi', 'SA'],
                ['Jocelyn', 'Bautista', 'PH'],
                ['Megan', 'Fournier', 'CA'],
                ['Holly', 'Braithwaite', 'GB'],
            ],
            'roles' => [
                [
                    'title' => 'Year 4 Class Teacher',
                    'employer' => 'Al Faisaliah International School, Riyadh',
                    'duty' => 'Taught the full primary curriculum to a mixed-ability class of 24 children aged eight to nine, planning English, mathematics, science and topic work across a two-week cycle.',
                    'skills' => ['Lesson planning', 'Differentiation', 'English National Curriculum'],
                ],
                [
                    'title' => 'Grade 2 Homeroom Teacher',
                    'employer' => 'Kingdom Schools, Riyadh',
                    'duty' => 'Delivered synthetic phonics and guided reading in daily thirty-minute groups, moving eighteen of twenty-six children up a reading band across one academic year.',
                    'skills' => ['Phonics', 'Guided reading', 'Reading-age tracking'],
                ],
                [
                    'title' => 'Primary Class Teacher (IB PYP)',
                    'employer' => 'Multinational School Riyadh',
                    'duty' => 'Planned and assessed against the IB Primary Years Programme, running two transdisciplinary units a term and documenting learner-profile evidence in pupil portfolios.',
                    'skills' => ['IB PYP', 'Portfolio assessment', 'Unit planning'],
                ],
                [
                    'title' => 'Year 5 Class Teacher',
                    'employer' => 'Manor Park Primary School, Manchester',
                    'duty' => 'Ran a Year 5 class of twenty-eight through the English National Curriculum, marking to a weekly feedback cycle and reporting to parents three times a year.',
                    'skills' => ['English National Curriculum', 'Report writing', 'Parent communication'],
                ],
                [
                    'title' => 'Grade 3 Mathematics Teacher',
                    'employer' => 'Dhahran Elementary School',
                    'duty' => 'Taught mathematics through concrete-pictorial-abstract mastery sequences, using Numicon and place-value apparatus with a lower-attaining set of nine.',
                    'skills' => ['Numicon', 'Mastery mathematics', 'Small-group intervention'],
                ],
                [
                    'title' => 'Year 6 Class Teacher and Phase Leader',
                    'employer' => 'The British School Riyadh',
                    'duty' => 'Led the upper primary team of four homeroom teachers, moderating writing samples and standardising the half-term formative assessment schedule.',
                    'skills' => ['Moderation', 'Formative assessment', 'Team leadership'],
                ],
                [
                    'title' => 'Primary Teacher (EAL focus)',
                    'employer' => 'Nasr City Language School, Cairo',
                    'duty' => 'Differentiated literacy and numeracy for four English-as-an-additional-language pupils and two with individual education plans, working alongside the learning support department.',
                    'skills' => ['EAL support', 'Individual education plans', 'Differentiation'],
                ],
                [
                    'title' => 'Grade 1 Homeroom Teacher',
                    'employer' => 'Sandton Primary School, Johannesburg',
                    'duty' => 'Supervised a class of twenty-two seven-year-olds through daily routines, playground duty and educational visits, and kept the behaviour log the phase leader reviewed weekly.',
                    'skills' => ['Behaviour management', 'Classroom management', 'Safeguarding'],
                ],
                [
                    'title' => 'Year 6 Transition Teacher',
                    'employer' => 'Amman Baccalaureate School',
                    'duty' => 'Prepared Year 6 pupils for secondary transition, running booster groups in reading comprehension and arithmetic three mornings a week.',
                    'skills' => ['Transition planning', 'Reading comprehension', 'Small-group intervention'],
                ],
                [
                    'title' => 'Primary Teaching Assistant',
                    'employer' => "St Brigid's National School, Dublin",
                    'duty' => 'Supported two primary classes in literacy and numeracy stations, prepared resources for the class teacher and supervised the lunch hall on a rota.',
                    'skills' => ['Classroom support', 'Resource preparation', 'Safeguarding'],
                ],
            ],
            'educations' => [
                [
                    'degree' => 'Bachelor of Education',
                    'field' => 'Primary Education',
                    'institution' => 'University of Manchester',
                    'years' => 4,
                    'description' => 'Four-year initial teacher training with two school placements in Key Stage 2 and a dissertation on early reading intervention.',
                ],
                [
                    'degree' => 'Postgraduate Certificate in Education',
                    'field' => 'Primary Teaching',
                    'institution' => 'University of Leeds',
                    'years' => 1,
                    'description' => 'One-year PGCE with qualified teacher status, specialising in the five to eleven age range across two contrasting placement schools.',
                ],
                [
                    'degree' => 'Bachelor of Arts',
                    'field' => 'Education',
                    'institution' => 'Cairo University',
                    'years' => 4,
                    'description' => 'Four-year education degree covering child development, classroom management and Arabic and English language teaching methods.',
                ],
                [
                    'degree' => 'Higher Diploma',
                    'field' => 'Foundation and Intermediate Phase Teaching',
                    'institution' => 'University of South Africa',
                    'years' => 2,
                    'description' => 'Part-time diploma in foundation and intermediate phase teaching, completed while working as a classroom assistant.',
                ],
                [
                    'degree' => 'Bachelor of Science',
                    'field' => 'Elementary Education',
                    'institution' => 'University of the Philippines',
                    'years' => 4,
                    'description' => 'Elementary education degree with a mathematics concentration and one semester of supervised student teaching.',
                ],
            ],
            'skills' => [
                'Classroom management', 'Lesson planning', 'Formative assessment', 'Parent communication',
                'Behaviour management', 'Google Classroom', 'Report writing', 'Safeguarding',
                'Differentiation', 'Educational visits',
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function stemTeachers(): array
    {
        return [
            'label' => 'Secondary STEM',
            'posting' => 'secondary-mathematics-teacher',
            'posting_alt' => 'reading-volunteer-primary-library',
            'max_educations' => 3,
            'people' => [
                ['Arjun', 'Nair', 'IN'],
                ['Hossam', 'El Sayed', 'EG'],
                ['Robert', 'Kenworthy', 'GB'],
                ['Bilal', 'Sheikh', 'PK'],
                ['Lina', 'Haddad', 'JO'],
                ['Sipho', 'Ndlovu', 'ZA'],
                ['Deepa', 'Krishnan', 'IN'],
                ['Abdulaziz', 'Al Harbi', 'SA'],
                ['Karim', 'Nassar', 'LB'],
                ['Jason', 'Whitaker', 'US'],
                ['Amany', 'Fahmy', 'EG'],
            ],
            'roles' => [
                [
                    'title' => 'IGCSE Mathematics Teacher',
                    'employer' => 'Riyadh Schools, Riyadh',
                    'duty' => 'Taught IGCSE mathematics on the Edexcel 9-1 specification to four sets across Years 10 and 11, with 82 per cent of the cohort at grade 5 or above.',
                    'skills' => ['IGCSE Mathematics', 'Exam board moderation', 'Data-driven intervention'],
                ],
                [
                    'title' => 'A-Level Physics Teacher',
                    'employer' => 'American International School Riyadh',
                    'duty' => 'Delivered A-Level physics to Years 12 and 13, running the full practical endorsement and preparing candidates for engineering and medicine applications.',
                    'skills' => ['A-Level Physics', 'Practical assessment', 'University applications'],
                ],
                [
                    'title' => 'IB Diploma Biology Teacher',
                    'employer' => 'Multinational School Riyadh',
                    'duty' => 'Taught IB Diploma biology at higher and standard level, supervising twelve internal assessments a year and marking against the current rubric.',
                    'skills' => ['IB Diploma Programme', 'Internal assessment supervision', 'Laboratory management'],
                ],
                [
                    'title' => 'Key Stage 3 Science Teacher',
                    'employer' => 'Delhi Public School, Sharjah',
                    'duty' => 'Ran Key Stage 3 science as a three-subject rotation for 150 students, wrote the chemistry scheme of work and managed a shared preparation room.',
                    'skills' => ['Scheme of work design', 'Laboratory management', 'Risk assessment'],
                ],
                [
                    'title' => 'MYP Mathematics Teacher',
                    'employer' => 'Modern American School, Amman',
                    'duty' => 'Taught MYP mathematics in Years 7 to 9, planning interdisciplinary units with the design department and reporting against the four MYP criteria.',
                    'skills' => ['MYP criteria assessment', 'Interdisciplinary planning', 'IB Diploma Programme'],
                ],
                [
                    'title' => 'Mathematics Teacher and Olympiad Coach',
                    'employer' => 'Karachi Grammar School',
                    'duty' => 'Prepared students for the national mathematics olympiad rounds, coaching a squad of fourteen in weekly after-school problem-solving sessions.',
                    'skills' => ['Olympiad coaching', 'Problem solving', 'Enrichment programmes'],
                ],
                [
                    'title' => 'Chemistry Teacher (Grades 9 to 12)',
                    'employer' => 'Victoria College, Alexandria',
                    'duty' => 'Taught chemistry to Grades 9 to 12 including Advanced Placement, and managed laboratory stock, chemical hazard records and risk assessments for practical work.',
                    'skills' => ['AP Chemistry', 'Laboratory management', 'Risk assessment'],
                ],
                [
                    'title' => 'Mathematics Teacher (Digital Lead)',
                    'employer' => 'Pinelands High School, Cape Town',
                    'duty' => 'Introduced GeoGebra and Desmos into the Year 9 algebra scheme and led two twilight sessions training the department to use them.',
                    'skills' => ['GeoGebra', 'Scheme of work design', 'Staff training'],
                ],
                [
                    'title' => 'Computer Science and ICT Teacher',
                    'employer' => 'King Fahd Academy',
                    'duty' => 'Taught computer science at IGCSE and A-Level — Python programming, data representation and the theory papers — alongside a robotics club of twenty.',
                    'skills' => ['Python', 'Computer science curriculum', 'Robotics club'],
                ],
                [
                    'title' => 'Laboratory Technician',
                    'employer' => 'Riyadh Schools, Riyadh',
                    'duty' => 'Prepared practical apparatus and solutions for a science department of nine, maintained the chemical inventory and disposed of waste to the safety schedule.',
                    'skills' => ['Laboratory management', 'Risk assessment', 'Stock control'],
                ],
            ],
            'educations' => [
                [
                    'degree' => 'Bachelor of Science',
                    'field' => 'Mathematics',
                    'institution' => 'University of Delhi',
                    'years' => 3,
                    'description' => 'Three-year mathematics degree covering analysis, algebra and statistics, followed by a one-year Bachelor of Education.',
                ],
                [
                    'degree' => 'Master of Science',
                    'field' => 'Physics',
                    'institution' => 'Cairo University',
                    'years' => 2,
                    'description' => 'Research masters in condensed matter physics with two years of undergraduate laboratory demonstrating.',
                ],
                [
                    'degree' => 'Postgraduate Certificate in Education',
                    'field' => 'Secondary Science',
                    'institution' => 'University of Birmingham',
                    'years' => 1,
                    'description' => 'PGCE with qualified teacher status in secondary science, physics specialism, across two placement schools.',
                ],
                [
                    'degree' => 'Bachelor of Engineering',
                    'field' => 'Chemical Engineering',
                    'institution' => 'NED University of Engineering and Technology',
                    'years' => 4,
                    'description' => 'Engineering degree converted to teaching through a two-year in-service education diploma.',
                ],
                [
                    'degree' => 'Bachelor of Science',
                    'field' => 'Biology and Chemistry',
                    'institution' => 'University of Cape Town',
                    'years' => 3,
                    'description' => 'Double major in biology and chemistry with a postgraduate certificate in education in the further education and training phase.',
                ],
            ],
            'skills' => [
                'IGCSE Mathematics', 'A-Level teaching', 'IB Diploma Programme', 'Laboratory management',
                'Practical assessment', 'Scheme of work design', 'Data-driven intervention',
                'Exam board moderation', 'Risk assessment', 'Formative assessment',
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function humanitiesTeachers(): array
    {
        return [
            'label' => 'Secondary humanities',
            'posting' => 'secondary-english-teacher',
            'posting_alt' => 'school-bus-driver',
            'max_educations' => 3,
            'people' => [
                ['Charlotte', 'Pemberton', 'GB'],
                ['Michael', 'Reinhardt', 'US'],
                ['Sinead', 'O Callaghan', 'IE'],
                ['Zanele', 'Dlamini', 'ZA'],
                ['Yasmin', 'Abdel Rahman', 'EG'],
                ['Omar', 'Zawahreh', 'JO'],
                ['Lama', 'Hourani', 'SY'],
                ['Patrick', 'Beaulieu', 'CA'],
                ['Georgia', 'Hollingsworth', 'GB'],
                ['Nathan', 'Kirkland', 'AU'],
                ['Sarah', 'Al Dosari', 'SA'],
            ],
            'roles' => [
                [
                    'title' => 'IGCSE English Language and Literature Teacher',
                    'employer' => 'The British School Riyadh',
                    'duty' => 'Taught IGCSE English language and literature to three sets in Years 10 and 11, teaching the poetry anthology and coursework portfolio to first-language and second-language candidates.',
                    'skills' => ['IGCSE English', 'Coursework moderation', 'Essay feedback'],
                ],
                [
                    'title' => 'IB Diploma English A Teacher',
                    'employer' => 'Multinational School Riyadh',
                    'duty' => 'Delivered IB Diploma English A: language and literature at higher level, running individual oral assessments and the written task cycle.',
                    'skills' => ['IB Diploma English A', 'Oral assessment', 'Essay feedback'],
                ],
                [
                    'title' => 'Secondary History Teacher',
                    'employer' => 'Cairo American College',
                    'duty' => 'Taught history across Key Stages 3 to 5, wrote a new Year 9 scheme on the twentieth-century Middle East and ran source-analysis workshops before examinations.',
                    'skills' => ['Source analysis', 'Scheme of work design', 'Essay feedback'],
                ],
                [
                    'title' => 'Geography Teacher (Years 7 to 11)',
                    'employer' => 'St Andrews College, Grahamstown',
                    'duty' => 'Taught geography to Years 7 to 11 including fieldwork weeks in Wadi Hanifah, teaching GIS mapping and the coursework write-up.',
                    'skills' => ['GIS mapping', 'Fieldwork supervision', 'Coursework moderation'],
                ],
                [
                    'title' => 'French Teacher (Years 7 to 11)',
                    'employer' => 'Belvedere College, Dublin',
                    'duty' => 'Taught French from beginner to IGCSE, running weekly speaking clinics and entering candidates for the DELF A2 and B1 examinations.',
                    'skills' => ['DELF preparation', 'Modern foreign languages', 'Oral assessment'],
                ],
                [
                    'title' => 'Business Studies and Economics Teacher',
                    'employer' => 'International School of Choueifat, Riyadh',
                    'duty' => 'Taught business studies and economics at IGCSE and A-Level, supervised the Young Enterprise team and used case-study assessment throughout.',
                    'skills' => ['Case-study assessment', 'Enterprise education', 'A-Level teaching'],
                ],
                [
                    'title' => 'EAL Teacher',
                    'employer' => 'Amman Academy',
                    'duty' => 'Supported twenty-four English-as-an-additional-language students in mainstream humanities lessons, writing individual language plans and co-teaching with subject staff.',
                    'skills' => ['EAL language plans', 'Co-teaching', 'Reading-age tracking'],
                ],
                [
                    'title' => 'Social Studies Teacher (Grades 6 to 9)',
                    'employer' => 'Cairo British School',
                    'duty' => 'Taught social studies to Grades 6 to 9, embedding debate and structured argument, and coordinated the Model United Nations delegation.',
                    'skills' => ['Debate coaching', 'Model United Nations', 'Source analysis'],
                ],
                [
                    'title' => 'Key Stage 4 English Intervention Lead',
                    'employer' => 'The British School Riyadh',
                    'duty' => 'Ran the Key Stage 4 English intervention programme for thirty borderline candidates, tracking reading ages and closing the gap by an average of fourteen months.',
                    'skills' => ['Reading-age tracking', 'Data-driven intervention', 'IGCSE English'],
                ],
            ],
            'educations' => [
                [
                    'degree' => 'Bachelor of Arts',
                    'field' => 'English Literature',
                    'institution' => 'University of Leeds',
                    'years' => 3,
                    'description' => 'Three-year literature degree with a dissertation on postcolonial fiction, followed by a PGCE in secondary English.',
                ],
                [
                    'degree' => 'Master of Arts',
                    'field' => 'Modern Middle Eastern History',
                    'institution' => 'American University in Cairo',
                    'years' => 2,
                    'description' => 'Masters in modern Middle Eastern history with a thesis on twentieth-century education policy.',
                ],
                [
                    'degree' => 'Bachelor of Arts',
                    'field' => 'Geography',
                    'institution' => 'University of Pretoria',
                    'years' => 3,
                    'description' => 'Geography degree with GIS and physical geography options and a postgraduate certificate in education.',
                ],
                [
                    'degree' => 'Bachelor of Arts',
                    'field' => 'French and Linguistics',
                    'institution' => 'University College Dublin',
                    'years' => 4,
                    'description' => 'Four-year degree with a year abroad in Lyon, certified at DALF C1.',
                ],
                [
                    'degree' => 'Postgraduate Diploma',
                    'field' => 'Teaching English to Speakers of Other Languages',
                    'institution' => 'University of Nottingham',
                    'years' => 1,
                    'description' => 'Postgraduate diploma in English language teaching with an assessed teaching practice portfolio.',
                ],
            ],
            'skills' => [
                'IGCSE English', 'Essay feedback', 'Source analysis', 'Coursework moderation',
                'Debate coaching', 'EAL language plans', 'Reading-age tracking', 'Formative assessment',
                'Oral assessment', 'Scheme of work design',
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function arabicTeachers(): array
    {
        return [
            'label' => 'Arabic and Islamic',
            'posting' => 'arabic-language-teacher-primary',
            'posting_alt' => 'it-support-technician',
            'max_educations' => 3,
            'people' => [
                ['Khalid', 'Al Ghamdi', 'SA'],
                ['Mahmoud', 'Abdel Latif', 'EG'],
                ['Osman', 'Bashir', 'SD'],
                ['Yaman', 'Al Halabi', 'SY'],
                ['Iman', 'Qasem', 'JO'],
                ['Nasser', 'Al Zahrani', 'SA'],
                ['Sohaila', 'Mansour', 'EG'],
                ['Youssef', 'Benali', 'MA'],
                ['Fatimah', 'Al Qahtani', 'SA'],
                ['Rasha', 'Kanaan', 'SY'],
                ['Awad', 'Nour', 'SD'],
            ],
            'roles' => [
                [
                    'title' => 'Arabic Language Teacher (Native Speakers)',
                    'employer' => 'Riyadh Al Kawthar Schools',
                    'duty' => 'Taught the Ministry of Education Arabic curriculum to Grades 4 to 6, covering grammar, dictation and composition, and prepared pupils for the national assessment.',
                    'skills' => ['Arabic grammar (nahw)', 'Ministry of Education curriculum', 'Arabic composition'],
                ],
                [
                    'title' => 'Arabic as a Second Language Teacher',
                    'employer' => 'Al Rowad International Schools, Riyadh',
                    'duty' => 'Taught Arabic as a second language to non-native speakers in Years 5 to 8, building from the alphabet to short connected paragraphs over three years.',
                    'skills' => ['Arabic as a second language', 'Handwriting and dictation', 'Oral examination'],
                ],
                [
                    'title' => 'Islamic Studies Teacher',
                    'employer' => 'Manarat Al Riyadh Schools',
                    'duty' => 'Taught Islamic studies to Grades 7 to 9 — aqeedah, fiqh and seerah — with weekly memorisation targets and an end-of-unit oral examination.',
                    'skills' => ['Islamic studies curriculum', 'Oral examination', 'Memorisation targets'],
                ],
                [
                    'title' => 'Quran Memorisation Teacher',
                    'employer' => 'Al Andalus Private School, Khartoum',
                    'duty' => 'Supervised Quran memorisation circles for thirty pupils, hearing recitation daily and tracking tajweed corrections in individual records.',
                    'skills' => ['Quran memorisation', 'Tajweed', 'Individual record keeping'],
                ],
                [
                    'title' => 'Senior Arabic Teacher (Secondary)',
                    'employer' => 'Al Azhar Language Institute, Cairo',
                    'duty' => 'Taught Arabic literature and rhetoric to Grades 10 to 12, teaching classical poetry and preparing candidates for the general secondary certificate.',
                    'skills' => ['Balaghah', 'Arabic literature', 'Examination preparation'],
                ],
                [
                    'title' => 'Arabic Curriculum Writer',
                    'employer' => 'Manarat Al Riyadh Schools',
                    'duty' => 'Wrote the primary phase Arabic scheme of work, aligning Ministry outcomes with the international curriculum timetable across six year groups.',
                    'skills' => ['Ministry of Education curriculum', 'Scheme of work design', 'Arabic grammar (nahw)'],
                ],
                [
                    'title' => 'Tarbiyah and Social Studies Teacher',
                    'employer' => 'Islamic Educational College, Amman',
                    'duty' => 'Taught the tarbiyah and social studies rotation to Grades 3 to 5, integrating Saudi history and civics with Islamic values units.',
                    'skills' => ['Islamic studies curriculum', 'Social studies', 'Values education'],
                ],
                [
                    'title' => 'Arabic Support Teacher',
                    'employer' => 'Al Rowad International Schools, Riyadh',
                    'duty' => 'Ran the Arabic support programme for twenty-two returning expatriate pupils, closing gaps in reading fluency and handwriting over two terms.',
                    'skills' => ['Arabic as a second language', 'Handwriting and dictation', 'Small-group intervention'],
                ],
                [
                    'title' => 'Arabic Grammar Teacher (Middle School)',
                    'employer' => 'Riyadh Al Kawthar Schools',
                    'duty' => 'Taught Arabic grammar and morphology to Grades 8 and 9 and examined the oral component for the whole middle-school phase.',
                    'skills' => ['Arabic grammar (nahw)', 'Oral examination', 'Arabic composition'],
                ],
            ],
            'educations' => [
                [
                    'degree' => 'Bachelor of Arts',
                    'field' => 'Arabic Language and Literature',
                    'institution' => 'King Saud University',
                    'years' => 4,
                    'description' => 'Four-year degree in Arabic language, grammar and classical literature, with a teaching diploma alongside.',
                ],
                [
                    'degree' => 'Bachelor of Arts',
                    'field' => 'Islamic Studies',
                    'institution' => 'Al-Azhar University',
                    'years' => 4,
                    'description' => 'Degree in usul al-din covering aqeedah, fiqh, hadith and tafsir, with a certified chain in Quranic recitation.',
                ],
                [
                    'degree' => 'Master of Arts',
                    'field' => 'Arabic Language Teaching Methods',
                    'institution' => 'Imam Mohammad Ibn Saud Islamic University',
                    'years' => 2,
                    'description' => 'Masters in teaching Arabic to non-native speakers, with a thesis on vocabulary acquisition in primary learners.',
                ],
                [
                    'degree' => 'Higher Diploma',
                    'field' => 'Education',
                    'institution' => 'University of Khartoum',
                    'years' => 2,
                    'description' => 'Postgraduate education diploma taken after an Arabic degree, focusing on secondary phase pedagogy.',
                ],
                [
                    'degree' => 'Ijazah',
                    'field' => 'Quranic Recitation',
                    'institution' => 'Riyadh Institute for Quranic Studies',
                    'years' => 3,
                    'description' => 'Certified chain of transmission in Hafs an Asim, with tajweed and comparative recitation study.',
                ],
            ],
            'skills' => [
                'Arabic grammar (nahw)', 'Tajweed', 'Quran memorisation', 'Islamic studies curriculum',
                'Arabic as a second language', 'Ministry of Education curriculum', 'Arabic composition',
                'Oral examination', 'Handwriting and dictation', 'Classroom management',
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function earlyYears(): array
    {
        return [
            'label' => 'Early years',
            'posting' => 'kindergarten-homeroom-teacher',
            'posting_alt' => 'head-of-science',
            'max_educations' => 3,
            'people' => [
                ['Maricel', 'Delos Santos', 'PH'],
                ['Lucy', 'Ainsworth', 'GB'],
                ['Annelie', 'Botha', 'ZA'],
                ['Doaa', 'Kamal', 'EG'],
                ['Priya', 'Menon', 'IN'],
                ['Noura', 'Al Subaie', 'SA'],
                ['Rosalie', 'Aguilar', 'PH'],
                ['Aoife', 'Gallagher', 'IE'],
                ['Dana', 'Sweiss', 'JO'],
                ['Maya', 'Chidiac', 'LB'],
            ],
            'roles' => [
                [
                    'title' => 'Early Years Teacher (FS1)',
                    'employer' => 'Little Academy Nursery, Riyadh',
                    'duty' => 'Taught a Foundation Stage 1 class of eighteen children aged three to four, planning through continuous provision and recording observations against the Early Years Foundation Stage framework.',
                    'skills' => ['Early Years Foundation Stage', 'Continuous provision', 'Observation and assessment'],
                ],
                [
                    'title' => 'KG2 Class Teacher',
                    'employer' => 'Al Yasmin Kindergarten, Riyadh',
                    'duty' => 'Ran a KG2 class of twenty five-year-olds, teaching early phonics, number recognition to twenty and fine-motor development through daily structured play.',
                    'skills' => ['Early phonics', 'Fine-motor development', 'Play-based learning'],
                ],
                [
                    'title' => 'Nursery Teacher',
                    'employer' => 'Bright Beginnings Preschool, Manila',
                    'duty' => 'Planned a play-based curriculum around half-termly themes, setting up sand, water, role-play and construction areas and rotating them fortnightly.',
                    'skills' => ['Play-based learning', 'Continuous provision', 'Theme planning'],
                ],
                [
                    'title' => 'Reception Class Teacher',
                    'employer' => 'Sunny Days Nursery, Cape Town',
                    'duty' => 'Kept individual learning journals with photographic evidence for twenty-two children and led termly parent consultations on next steps.',
                    'skills' => ['Learning journals', 'Parent partnership', 'Observation and assessment'],
                ],
                [
                    'title' => 'Early Years Practitioner',
                    'employer' => 'Happy Hearts Nursery, Dublin',
                    'duty' => 'Settled new entrants through a staggered induction, working with two teaching assistants on separation anxiety and toilet training routines.',
                    'skills' => ['Settling and induction', 'Team working', 'Safeguarding'],
                ],
                [
                    'title' => 'Reception Phonics Teacher',
                    'employer' => 'Little Academy Nursery, Riyadh',
                    'duty' => 'Taught reception phonics in daily twenty-minute sessions and assessed against the early years profile at the end of the year.',
                    'skills' => ['Early phonics', 'Observation and assessment', 'Early Years Foundation Stage'],
                ],
                [
                    'title' => 'Bilingual KG1 Teacher',
                    'employer' => 'Al Tarbiyah Al Islamiyah Kindergarten, Cairo',
                    'duty' => 'Delivered a bilingual Arabic and English early years programme, with story time, songs and circle time in both languages every day.',
                    'skills' => ['Circle time', 'Bilingual early years', 'Play-based learning'],
                ],
                [
                    'title' => 'Outdoor Learning Lead (Early Years)',
                    'employer' => 'Al Yasmin Kindergarten, Riyadh',
                    'duty' => 'Supervised outdoor learning and gross-motor sessions, planning obstacle courses and balance activities twice weekly for two kindergarten classes.',
                    'skills' => ['Outdoor learning', 'Gross-motor development', 'Risk awareness'],
                ],
                [
                    'title' => 'Early Intervention Practitioner',
                    'employer' => 'Bright Beginnings Preschool, Manila',
                    'duty' => 'Wrote and delivered early intervention plans for four children with speech and language delay, in liaison with a visiting therapist.',
                    'skills' => ['Early intervention', 'Speech and language support', 'Parent partnership'],
                ],
            ],
            'educations' => [
                [
                    'degree' => 'Bachelor of Arts',
                    'field' => 'Early Childhood Education',
                    'institution' => 'University of the Philippines',
                    'years' => 4,
                    'description' => 'Early childhood degree covering child development, play pedagogy and observation-based assessment.',
                ],
                [
                    'degree' => 'Level 3 Diploma',
                    'field' => 'Early Years Education and Care',
                    'institution' => 'The Manchester College',
                    'years' => 2,
                    'description' => 'Vocational diploma with 750 supervised placement hours across nursery and reception settings.',
                ],
                [
                    'degree' => 'Bachelor of Education',
                    'field' => 'Foundation Phase Teaching',
                    'institution' => 'University of the Free State',
                    'years' => 4,
                    'description' => 'Foundation phase degree specialising in the birth to six age range, with two extended teaching practicums.',
                ],
                [
                    'degree' => 'Diploma',
                    'field' => 'Montessori Early Childhood Education',
                    'institution' => 'Montessori Centre, Cairo',
                    'years' => 1,
                    'description' => 'Montessori three to six diploma with 300 hours of directed practice in a mixed-age classroom.',
                ],
                [
                    'degree' => 'Bachelor of Arts',
                    'field' => 'Kindergarten Education',
                    'institution' => 'Ain Shams University',
                    'years' => 4,
                    'description' => 'Kindergarten education degree covering early literacy in Arabic, child psychology and parent partnership.',
                ],
            ],
            'skills' => [
                'Early Years Foundation Stage', 'Play-based learning', 'Learning journals', 'Early phonics',
                'Continuous provision', 'Observation and assessment', 'Montessori method',
                'Parent partnership', 'Circle time', 'Safeguarding',
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function leadership(): array
    {
        return [
            'label' => 'Leadership',
            'posting' => 'head-of-science',
            'posting_alt' => 'examination-invigilator',
            'max_educations' => 3,
            'people' => [
                ['Andrew', 'Hollingworth', 'GB'],
                ['Faisal', 'Al Rasheed', 'SA'],
                ['Karen', 'Delacroix', 'US'],
                ['Declan', 'MacCarthy', 'IE'],
                ['Sherif', 'Mansour', 'EG'],
                ['Gavin', 'Pretorius', 'ZA'],
                ['Hanan', 'Abu Saleh', 'JO'],
                ['Rebecca', 'Thornbury', 'GB'],
                ['Marc', 'Tremblay', 'CA'],
                ['Maha', 'Al Suwailem', 'SA'],
            ],
            'roles' => [
                [
                    'title' => 'Head of Mathematics Department',
                    'employer' => 'The British School Riyadh',
                    'duty' => 'Led a mathematics department of eleven across two campuses, setting the assessment calendar, running lesson observations and raising the IGCSE grade 7 to 9 share from 31 to 44 per cent over three years.',
                    'skills' => ['Department leadership', 'Lesson observation', 'Data-driven school improvement'],
                ],
                [
                    'title' => 'IB Diploma Programme Coordinator',
                    'employer' => 'Multinational School Riyadh',
                    'duty' => 'Coordinated the IB Diploma Programme for 180 students, managing internal assessment deadlines, the CAS programme and the five-year authorisation and evaluation cycle with the IB.',
                    'skills' => ['IB authorisation', 'Programme coordination', 'Curriculum mapping'],
                ],
                [
                    'title' => 'Deputy Head (Academic)',
                    'employer' => 'Kingdom Schools, Riyadh',
                    'duty' => 'Held academic line management for Years 7 to 11, chaired the data review cycle each half term and ran the appraisal process for thirty-four teaching staff.',
                    'skills' => ['Appraisal and performance management', 'Data-driven school improvement', 'Timetabling'],
                ],
                [
                    'title' => 'Primary Phase Leader',
                    'employer' => 'Dubai British School',
                    'duty' => 'Led a primary phase of 620 pupils and forty-two staff, owning the curriculum map, the intervention budget and transition arrangements into secondary.',
                    'skills' => ['Curriculum mapping', 'Budget management', 'Department leadership'],
                ],
                [
                    'title' => 'Curriculum Coordinator',
                    'employer' => 'Cairo English School',
                    'duty' => 'Rewrote the whole-school assessment policy, introducing standardised baseline testing and a single reporting format across three curricula.',
                    'skills' => ['Curriculum mapping', 'Assessment policy', 'School improvement planning'],
                ],
                [
                    'title' => 'Head of Secondary',
                    'employer' => 'St Cyprians School, Cape Town',
                    'duty' => 'Managed accreditation with the Council of International Schools and the Ministry inspection cycle, coordinating self-study across nine departments.',
                    'skills' => ['CIS accreditation', 'School improvement planning', 'Department leadership'],
                ],
                [
                    'title' => 'Assistant Principal (Staffing)',
                    'employer' => 'American International School Riyadh',
                    'duty' => 'Led recruitment for twenty-eight teaching posts in one season, from job description through overseas interview panels to visa onboarding with the HR department.',
                    'skills' => ['Teacher recruitment', 'Appraisal and performance management', 'Budget management'],
                ],
                [
                    'title' => 'Assistant Principal (Pastoral)',
                    'employer' => 'Kingdom Schools, Riyadh',
                    'duty' => 'Chaired the safeguarding and pastoral committee, rebuilt the behaviour policy around restorative practice and halved fixed-term exclusions.',
                    'skills' => ['Safeguarding lead', 'Behaviour policy', 'Pastoral leadership'],
                ],
                [
                    'title' => 'Principal',
                    'employer' => 'Al Rowad International Schools, Riyadh',
                    'duty' => 'Reported to the board on academic outcomes, staffing and the professional development budget, and led the three-year school improvement plan.',
                    'skills' => ['School improvement planning', 'Budget management', 'Governance reporting'],
                ],
            ],
            'educations' => [
                [
                    'degree' => 'Master of Education',
                    'field' => 'Educational Leadership',
                    'institution' => 'University of Nottingham',
                    'years' => 2,
                    'description' => 'Masters in educational leadership and management with a dissertation on middle leadership in international schools.',
                ],
                [
                    'degree' => 'National Professional Qualification for Senior Leadership',
                    'field' => 'School Leadership',
                    'institution' => 'National College for School Leadership',
                    'years' => 1,
                    'description' => 'Assessed whole-school improvement project on assessment for learning, completed in post.',
                ],
                [
                    'degree' => 'Bachelor of Science',
                    'field' => 'Mathematics',
                    'institution' => 'University of Bristol',
                    'years' => 3,
                    'description' => 'Mathematics degree followed by a PGCE and fifteen years of secondary teaching before moving into leadership.',
                ],
                [
                    'degree' => 'Master of Business Administration',
                    'field' => 'Education Management',
                    'institution' => 'King Saud University',
                    'years' => 2,
                    'description' => 'MBA with an education management concentration covering finance, governance and strategic planning.',
                ],
                [
                    'degree' => 'IB Certificate',
                    'field' => 'Leading the Diploma Programme',
                    'institution' => 'International Baccalaureate Organisation',
                    'years' => 1,
                    'description' => 'IB professional development certificate in programme leadership, categories one and three.',
                ],
            ],
            'skills' => [
                'Department leadership', 'Lesson observation', 'Appraisal and performance management',
                'Data-driven school improvement', 'IB authorisation', 'CIS accreditation', 'Timetabling',
                'Budget management', 'Safeguarding lead', 'Teacher recruitment', 'Curriculum mapping',
                'School improvement planning',
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function frontOffice(): array
    {
        return [
            'label' => 'Admin and office',
            'posting' => 'school-registrar',
            'posting_alt' => 'secondary-mathematics-teacher',
            'max_educations' => 2,
            'people' => [
                ['Hessa', 'Al Mutairi', 'SA'],
                ['Turki', 'Al Shammari', 'SA'],
                ['Mona', 'Farouk', 'EG'],
                ['Cristina', 'Villanueva', 'PH'],
                ['Salma', 'Odeh', 'JO'],
                ['Bandar', 'Al Juhani', 'SA'],
                ['Hind', 'Mohamed Ali', 'SD'],
                ['Nadine', 'Khoury', 'LB'],
                ['Latifa', 'Al Suhaimi', 'SA'],
                ['Shalini', 'Raghavan', 'IN'],
                ['Ghada', 'Barakat', 'SY'],
            ],
            'roles' => [
                [
                    'title' => 'Admissions Officer',
                    'employer' => 'Kingdom Schools, Riyadh',
                    'duty' => 'Ran admissions for a 1,400-pupil school from enquiry to enrolment, scheduling entrance assessments and completing 260 new registrations in one intake season.',
                    'skills' => ['Admissions administration', 'PowerSchool', 'Arabic and English correspondence'],
                ],
                [
                    'title' => 'School Registrar',
                    'employer' => 'Al Rowad International Schools, Riyadh',
                    'duty' => 'Maintained student records in PowerSchool and the Ministry of Education Noor system, reconciling 1,100 files each term and issuing transfer certificates.',
                    'skills' => ['Ministry of Education Noor system', 'Records management', 'PowerSchool'],
                ],
                [
                    'title' => 'Front Office Administrator',
                    'employer' => 'Riyadh Najd Schools',
                    'duty' => 'Staffed the front desk for forty to sixty visitors a day, handling the switchboard, visitor badging, the late-arrival log and early-collection authorisations.',
                    'skills' => ['Switchboard and reception', 'Visitor safeguarding checks', 'Data entry'],
                ],
                [
                    'title' => 'Executive Assistant to the Principal',
                    'employer' => 'Al Faisaliah Group, Riyadh',
                    'duty' => 'Managed the principal\'s diary, board papers and travel, and minuted the weekly senior leadership meeting.',
                    'skills' => ['Diary management', 'Minute taking', 'Arabic and English correspondence'],
                ],
                [
                    'title' => 'Accounts and Fees Clerk',
                    'employer' => 'Modern Language School, Cairo',
                    'duty' => 'Prepared and issued fee invoices for 900 families in coordination with the finance office and chased sixty-day arrears to a written schedule.',
                    'skills' => ['Fee invoicing', 'Microsoft Excel', 'Records management'],
                ],
                [
                    'title' => 'Transport Coordinator',
                    'employer' => 'Emirates National School, Abu Dhabi',
                    'duty' => 'Coordinated the school bus roster for 380 pupils across eleven routes, including daily absence changes and parent notifications.',
                    'skills' => ['Rostering', 'Parent notifications', 'Data entry'],
                ],
                [
                    'title' => 'Student Records Clerk',
                    'employer' => 'Kingdom Schools, Riyadh',
                    'duty' => 'Processed 340 re-enrolment contracts, chased outstanding medical and immunisation records and prepared the census return for the Ministry.',
                    'skills' => ['Records management', 'Ministry of Education Noor system', 'Data entry'],
                ],
                [
                    'title' => 'Admissions and Marketing Coordinator',
                    'employer' => 'Al Rowad International Schools, Riyadh',
                    'duty' => 'Handled admissions enquiries in Arabic and English by phone, WhatsApp and email, and ran school tours for prospective parents twice a week.',
                    'skills' => ['Admissions administration', 'Arabic and English correspondence', 'Customer service'],
                ],
                [
                    'title' => 'HR Administrator',
                    'employer' => 'Riyadh Najd Schools',
                    'duty' => 'Kept staff attendance and leave records for 120 employees and prepared the monthly report the HR department signed off.',
                    'skills' => ['Records management', 'Microsoft Excel', 'Data entry'],
                ],
            ],
            'educations' => [
                [
                    'degree' => 'Bachelor of Arts',
                    'field' => 'Business Administration',
                    'institution' => 'King Saud University',
                    'years' => 4,
                    'description' => 'Business administration degree with an office management and human resources concentration.',
                ],
                [
                    'degree' => 'Diploma',
                    'field' => 'Office Administration',
                    'institution' => 'Riyadh Technical College',
                    'years' => 2,
                    'description' => 'Two-year diploma in office administration, records management and business correspondence in Arabic and English.',
                ],
                [
                    'degree' => 'Bachelor of Science',
                    'field' => 'Information Management',
                    'institution' => 'University of Santo Tomas',
                    'years' => 4,
                    'description' => 'Degree in records and information management with a database administration elective.',
                ],
                [
                    'degree' => 'Secondary School Certificate',
                    'field' => 'General Studies',
                    'institution' => 'Al Nahda Secondary School, Riyadh',
                    'years' => 3,
                    'description' => 'General secondary certificate; office skills gained on the job over eleven years.',
                ],
                [
                    'degree' => 'Bachelor of Commerce',
                    'field' => 'Accounting',
                    'institution' => 'Cairo University',
                    'years' => 4,
                    'description' => 'Commerce degree, followed by four years in a corporate accounts office before moving into school administration.',
                ],
            ],
            'skills' => [
                'PowerSchool', 'Ministry of Education Noor system', 'Admissions administration',
                'Records management', 'Switchboard and reception', 'Arabic and English correspondence',
                'Microsoft Excel', 'Fee invoicing', 'Diary management', 'Visitor safeguarding checks',
                'Data entry',
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function studentSupport(): array
    {
        return [
            'label' => 'Student support',
            'posting' => 'school-nurse',
            'posting_alt' => 'payroll-and-hr-officer',
            'max_educations' => 3,
            'people' => [
                ['Imelda', 'Ramos', 'PH'],
                ['Wafa', 'Al Amri', 'SA'],
                ['Anitha', 'Joseph', 'IN'],
                ['Hala', 'Sabry', 'EG'],
                ['Belinda', 'Naidoo', 'ZA'],
                ['Rachel', 'Summerfield', 'GB'],
                ['Grace', 'Manalo', 'PH'],
                ['Tariq', 'Nabulsi', 'JO'],
                ['Amal', 'Al Balawi', 'SA'],
                ['Joelle', 'Rizk', 'LB'],
                ['Saima', 'Yousaf', 'PK'],
            ],
            'roles' => [
                [
                    'title' => 'School Nurse',
                    'employer' => 'Al Rowad International Schools, Riyadh',
                    'duty' => 'Ran the health room for 1,200 pupils single-handed, treating twenty-five to forty presentations a day, maintaining the medication register and holding the anaphylaxis and asthma care plans.',
                    'skills' => ['Medication administration', 'Care plan writing', 'Paediatric first aid'],
                ],
                [
                    'title' => 'Paediatric Nurse',
                    'employer' => 'Dr Sulaiman Al Habib Medical Group, Riyadh',
                    'duty' => 'Kept immunisation and chronic-condition records for a whole school roll, ran annual vision and hearing screening and reported notifiable cases to the Ministry of Health.',
                    'skills' => ['Immunisation records', 'Health screening', 'Saudi Commission for Health Specialties registration'],
                ],
                [
                    'title' => 'School Counsellor',
                    'employer' => 'The British School Riyadh',
                    'duty' => 'Carried a counselling caseload of forty-five secondary students, running individual sessions on anxiety and examination stress and referring six to external clinical services.',
                    'skills' => ['Counselling (CBT-informed)', 'Safeguarding', 'External referrals'],
                ],
                [
                    'title' => 'Special Educational Needs Coordinator',
                    'employer' => 'Cairo British School',
                    'duty' => 'Coordinated provision for sixty-two pupils on the special educational needs register, writing individual education plans and chairing termly review meetings with parents.',
                    'skills' => ['Individual education plans', 'Provision mapping', 'Parent meetings'],
                ],
                [
                    'title' => 'Learning Support Teacher',
                    'employer' => 'Al Rowad International Schools, Riyadh',
                    'duty' => 'Delivered small-group and one-to-one literacy intervention for pupils with dyslexia, using structured multisensory programmes in twelve-week blocks.',
                    'skills' => ['Dyslexia intervention', 'Individual education plans', 'Multisensory literacy'],
                ],
                [
                    'title' => 'Librarian',
                    'employer' => 'Kingdom Schools, Riyadh',
                    'duty' => 'Ran a library of 18,000 titles, managed the Follett catalogue and the annual acquisition budget, and taught weekly information-literacy lessons to Years 3 to 6.',
                    'skills' => ['Follett Destiny', 'Cataloguing', 'Information literacy'],
                ],
                [
                    'title' => 'Speech and Language Assistant',
                    'employer' => 'Makati Medical Center, Manila',
                    'duty' => 'Supported four children against speech and language targets set by a visiting therapist, running daily fifteen-minute sessions and recording progress.',
                    'skills' => ['Speech and language support', 'Progress recording', 'Individual education plans'],
                ],
                [
                    'title' => 'Educational Psychologist',
                    'employer' => 'Dr Sulaiman Al Habib Medical Group, Riyadh',
                    'duty' => 'Administered and interpreted cognitive and diagnostic assessments, writing thirty psychological reports a year and advising teachers on classroom strategies.',
                    'skills' => ['Cognitive assessment', 'Report writing', 'Teacher consultation'],
                ],
                [
                    'title' => 'Health Room Nurse',
                    'employer' => 'Kingdom Hospital, Riyadh',
                    'duty' => 'Triaged walk-in paediatric presentations on a twelve-hour shift, administered prescribed medication and escalated to the duty physician against a written protocol.',
                    'skills' => ['Paediatric first aid', 'Medication administration', 'Triage'],
                ],
                [
                    'title' => 'Pastoral Support Lead',
                    'employer' => 'The British School Riyadh',
                    'duty' => 'Led safeguarding referrals for the secondary phase, kept the confidential log and liaised with the designated safeguarding lead weekly.',
                    'skills' => ['Safeguarding', 'Confidential record keeping', 'Counselling (CBT-informed)'],
                ],
            ],
            'educations' => [
                [
                    'degree' => 'Bachelor of Science',
                    'field' => 'Nursing',
                    'institution' => 'University of Santo Tomas',
                    'years' => 4,
                    'description' => 'Nursing degree with paediatric and community placements; registered with the Saudi Commission for Health Specialties.',
                ],
                [
                    'degree' => 'Master of Science',
                    'field' => 'Counselling Psychology',
                    'institution' => 'Ain Shams University',
                    'years' => 2,
                    'description' => 'Masters in counselling psychology with 400 supervised clinical hours in an adolescent service.',
                ],
                [
                    'degree' => 'Postgraduate Diploma',
                    'field' => 'Special Educational Needs Coordination',
                    'institution' => 'University of Birmingham',
                    'years' => 1,
                    'description' => 'National award for SEN coordination with an assessed provision-mapping project.',
                ],
                [
                    'degree' => 'Bachelor of Arts',
                    'field' => 'Library and Information Science',
                    'institution' => 'University of Delhi',
                    'years' => 3,
                    'description' => 'Library science degree covering cataloguing, classification and school library management.',
                ],
                [
                    'degree' => 'Bachelor of Science',
                    'field' => 'Nursing',
                    'institution' => 'King Saud bin Abdulaziz University for Health Sciences',
                    'years' => 4,
                    'description' => 'Nursing degree with a paediatric emergency rotation and Saudi Commission registration on graduation.',
                ],
            ],
            'skills' => [
                'Paediatric first aid', 'Medication administration', 'Immunisation records', 'Safeguarding',
                'Individual education plans', 'Dyslexia intervention', 'Counselling (CBT-informed)',
                'Follett Destiny', 'Cataloguing', 'Care plan writing',
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function technology(): array
    {
        return [
            'label' => 'IT and technical',
            'posting' => 'it-support-technician',
            'posting_alt' => 'kindergarten-homeroom-teacher',
            'max_educations' => 2,
            'people' => [
                ['Vikram', 'Desai', 'IN'],
                ['Usman', 'Tariq', 'PK'],
                ['Ahmed', 'Zaki', 'EG'],
                ['Sultan', 'Al Dossary', 'SA'],
                ['Ronald', 'Padilla', 'PH'],
                ['Sanjay', 'Iyer', 'IN'],
                ['Mohannad', 'Freij', 'JO'],
                ['Bassel', 'Hariri', 'SY'],
                ['Meshari', 'Al Anazi', 'SA'],
                ['Rakib', 'Hasan', 'BD'],
            ],
            'roles' => [
                [
                    'title' => 'IT Support Technician',
                    'employer' => 'Kingdom Schools, Riyadh',
                    'duty' => 'Supported 900 devices across three campuses — Windows laptops, iPads and interactive panels — closing sixty to eighty helpdesk tickets a week against a same-day target.',
                    'skills' => ['Helpdesk ticketing', 'Interactive panel support', 'Device imaging'],
                ],
                [
                    'title' => 'Network Administrator',
                    'employer' => 'Elm Company, Riyadh',
                    'duty' => 'Administered two Cisco switch stacks, forty-six wireless access points, VLAN segmentation for staff, student and guest traffic and the FortiGate content filter.',
                    'skills' => ['Cisco switching', 'VLAN configuration', 'FortiGate firewall'],
                ],
                [
                    'title' => 'Systems Administrator',
                    'employer' => 'STC Solutions, Riyadh',
                    'duty' => 'Ran Active Directory and Microsoft 365 for 1,600 accounts, automating the September rollover with PowerShell and enforcing conditional access and multi-factor authentication.',
                    'skills' => ['Active Directory', 'PowerShell', 'Microsoft 365 administration'],
                ],
                [
                    'title' => 'Educational Technology Coordinator',
                    'employer' => 'Al Rowad International Schools, Riyadh',
                    'duty' => 'Managed the Google Workspace for Education tenant, Chromebook enrolment for 400 students and single sign-on into the learning platform.',
                    'skills' => ['Google Workspace for Education', 'Chromebook enrolment', 'Single sign-on'],
                ],
                [
                    'title' => 'Security Systems Technician',
                    'employer' => 'Systems Limited, Lahore',
                    'duty' => 'Maintained a CCTV estate of 120 cameras and the access-control system, and kept the retention schedule the data protection policy required.',
                    'skills' => ['CCTV and access control', 'Retention scheduling', 'Cabling'],
                ],
                [
                    'title' => 'Audio-Visual Technician',
                    'employer' => 'Kingdom Schools, Riyadh',
                    'duty' => 'Set up and operated audio, lighting and streaming for assemblies, graduations and parent evenings, including a 600-seat auditorium.',
                    'skills' => ['Audio-visual operation', 'Live streaming', 'Event setup'],
                ],
                [
                    'title' => 'Infrastructure Engineer',
                    'employer' => 'Tech Mahindra, Pune',
                    'duty' => 'Rebuilt a server room after a power incident: replaced two hosts, restored the VMware cluster from Veeam backups and documented the recovery runbook.',
                    'skills' => ['VMware', 'Veeam backup', 'Disaster recovery'],
                ],
                [
                    'title' => 'ICT Trainer',
                    'employer' => 'Al Rowad International Schools, Riyadh',
                    'duty' => 'Trained teaching staff on the learning management system and interactive panels, running fourteen twilight workshops and writing the how-to guides.',
                    'skills' => ['Staff training', 'Interactive panel support', 'Documentation'],
                ],
                [
                    'title' => 'ICT Manager',
                    'employer' => 'Elm Company, Riyadh',
                    'duty' => 'Managed the ICT budget and supplier contracts, ran a three-year device refresh tender and kept the asset register for 1,100 items.',
                    'skills' => ['Asset management', 'Supplier management', 'Budget management'],
                ],
            ],
            'educations' => [
                [
                    'degree' => 'Bachelor of Technology',
                    'field' => 'Computer Science and Engineering',
                    'institution' => 'Vellore Institute of Technology',
                    'years' => 4,
                    'description' => 'Engineering degree with networking and operating systems specialisations; CCNA certified in the final year.',
                ],
                [
                    'degree' => 'Bachelor of Science',
                    'field' => 'Information Technology',
                    'institution' => 'National University of Sciences and Technology, Islamabad',
                    'years' => 4,
                    'description' => 'IT degree covering networks, databases and systems administration, with a final-year project on campus wireless design.',
                ],
                [
                    'degree' => 'Diploma',
                    'field' => 'Computer Networking',
                    'institution' => 'Riyadh College of Technology',
                    'years' => 2,
                    'description' => 'Two-year technical diploma in networking and hardware support; CompTIA A+ and Network+ certified.',
                ],
                [
                    'degree' => 'Bachelor of Science',
                    'field' => 'Computer Science',
                    'institution' => 'Cairo University',
                    'years' => 4,
                    'description' => 'Computer science degree, followed by three years of corporate helpdesk work before moving into school IT.',
                ],
                [
                    'degree' => 'Professional Certificate',
                    'field' => 'Microsoft 365 Administration',
                    'institution' => 'Microsoft Certified',
                    'years' => 1,
                    'description' => 'Microsoft 365 administrator expert certification, renewed annually.',
                ],
            ],
            'skills' => [
                'Active Directory', 'Microsoft 365 administration', 'PowerShell', 'Cisco switching',
                'VLAN configuration', 'FortiGate firewall', 'Google Workspace for Education',
                'VMware', 'Veeam backup', 'CCTV and access control', 'Helpdesk ticketing',
                'Asset management',
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function financeAndHr(): array
    {
        return [
            'label' => 'Finance and HR',
            'posting' => 'payroll-and-hr-officer',
            'posting_alt' => 'arabic-language-teacher-primary',
            'max_educations' => 3,
            'people' => [
                ['Ibrahim', 'Al Sulami', 'SA'],
                ['Ramesh', 'Pillai', 'IN'],
                ['Tamer', 'Abdel Aziz', 'EG'],
                ['Faizan', 'Ahmed', 'PK'],
                ['Norah', 'Al Faraj', 'SA'],
                ['Rami', 'Sharaf', 'JO'],
                ['Waleed', 'Al Mansour', 'SA'],
                ['Elie', 'Gerges', 'LB'],
                ['Marilou', 'Sarmiento', 'PH'],
                ['Sara', 'Al Hamdan', 'SA'],
            ],
            'roles' => [
                [
                    'title' => 'Finance Manager',
                    'employer' => 'Kingdom Schools, Riyadh',
                    'duty' => 'Ran the monthly close for a 1,400-pupil school — journals, accruals, bank reconciliation — and delivered a management pack to the board by day seven.',
                    'skills' => ['Month-end close', 'Budgeting and forecasting', 'Oracle Financials'],
                ],
                [
                    'title' => 'Accounts Receivable Accountant',
                    'employer' => 'Al Rowad International Schools, Riyadh',
                    'duty' => 'Managed the fee ledger for 900 families — invoicing, instalment plans, sibling discounts and a sixty-day arrears process — and cut year-end receivables by 22 per cent.',
                    'skills' => ['Fee ledger management', 'Odoo', 'Credit control'],
                ],
                [
                    'title' => 'Payroll Officer',
                    'employer' => 'Almarai, Riyadh',
                    'duty' => 'Processed payroll for 210 staff through the wage protection system, including GOSI contributions, end-of-service accruals and expatriate allowances.',
                    'skills' => ['Payroll (WPS)', 'GOSI', 'End-of-service benefits'],
                ],
                [
                    'title' => 'HR Officer',
                    'employer' => 'Nesma Holding, Riyadh',
                    'duty' => 'Handled the full employee lifecycle for 180 staff: offer letters, Qiwa contracts, Mudad payroll registration, leave records and end-of-service settlements.',
                    'skills' => ['Qiwa and Mudad', 'End-of-service benefits', 'Employee records'],
                ],
                [
                    'title' => 'HR and Government Relations Officer',
                    'employer' => 'Kingdom Schools, Riyadh',
                    'duty' => 'Ran government relations through Absher, Muqeem and Qiwa — sixty iqama renewals and twenty-two new work visas in a year — and held Saudization compliance in the green band.',
                    'skills' => ['Absher and Muqeem', 'Saudization compliance', 'Qiwa and Mudad'],
                ],
                [
                    'title' => 'Management Accountant',
                    'employer' => 'KPMG Al Fozan and Partners, Riyadh',
                    'duty' => 'Prepared the annual budget with nine department heads, modelled three enrolment scenarios and tracked variance monthly.',
                    'skills' => ['Budgeting and forecasting', 'Variance analysis', 'Month-end close'],
                ],
                [
                    'title' => 'Procurement Officer',
                    'employer' => 'Nesma Holding, Riyadh',
                    'duty' => 'Managed procurement — three-quote tendering, supplier contracts and the fixed-asset register — against an annual spend of six million riyals.',
                    'skills' => ['Procurement and tendering', 'Supplier management', 'Fixed-asset register'],
                ],
                [
                    'title' => 'Chief Accountant',
                    'employer' => 'Al Rowad International Schools, Riyadh',
                    'duty' => 'Prepared the VAT return and liaised with the external auditors through to a clean opinion for three consecutive years.',
                    'skills' => ['VAT returns', 'Statutory audit', 'Month-end close'],
                ],
                [
                    'title' => 'Recruitment and Onboarding Officer',
                    'employer' => 'Infosys BPM, Bangalore',
                    'duty' => 'Rebuilt the recruitment process onto an applicant tracking system, and ran onboarding and induction for thirty-four new joiners in one August.',
                    'skills' => ['Applicant tracking systems', 'Onboarding', 'Employee records'],
                ],
            ],
            'educations' => [
                [
                    'degree' => 'Bachelor of Commerce',
                    'field' => 'Accounting',
                    'institution' => 'King Saud University',
                    'years' => 4,
                    'description' => 'Accounting degree covering financial reporting, cost accounting and Saudi tax; part-qualified with SOCPA.',
                ],
                [
                    'degree' => 'Bachelor of Business Administration',
                    'field' => 'Human Resource Management',
                    'institution' => 'Bangalore University',
                    'years' => 3,
                    'description' => 'HR degree covering employment law, compensation and organisational behaviour.',
                ],
                [
                    'degree' => 'Master of Business Administration',
                    'field' => 'Finance',
                    'institution' => 'Cairo University',
                    'years' => 2,
                    'description' => 'MBA with a finance concentration and a capstone on working-capital management in service businesses.',
                ],
                [
                    'degree' => 'Professional Certificate',
                    'field' => 'Certified Public Accountant',
                    'institution' => 'Saudi Organization for Chartered and Professional Accountants',
                    'years' => 3,
                    'description' => 'SOCPA certification covering Saudi financial reporting standards, zakat and value added tax.',
                ],
                [
                    'degree' => 'Diploma',
                    'field' => 'Accounting and Payroll',
                    'institution' => 'Riyadh Technical College',
                    'years' => 2,
                    'description' => 'Two-year diploma in bookkeeping and payroll administration, trained on Oracle and Odoo.',
                ],
            ],
            'skills' => [
                'Month-end close', 'Fee ledger management', 'Payroll (WPS)', 'GOSI',
                'End-of-service benefits', 'Qiwa and Mudad', 'Absher and Muqeem',
                'Saudization compliance', 'Budgeting and forecasting', 'VAT returns',
                'Oracle Financials', 'Procurement and tendering',
            ],
        ];
    }

    /**
     * OPERATIONS SHARES NO VOCABULARY WITH TEACHING, and that is the family this
     * fixture exists to prove. If these thirteen embed anywhere near the primary
     * teachers, the descriptions above are too generic to cluster on.
     *
     * `max_educations` is 2 and the roles carry their own trade certificates: for
     * support and operational roles formal education is largely irrelevant, and the
     * scorer is told not to mark its absence down — so nobody here has a degree.
     *
     * @return array<string, mixed>
     */
    protected function operations(): array
    {
        $secondary = [
            'degree' => 'Secondary School Certificate',
            'field' => 'General Studies',
            'institution' => 'Government High School, Dhaka',
            'years' => 3,
            'description' => 'Completed secondary schooling; every trade skill since has been learned on the job and through employer training.',
        ];

        $safety = [
            'degree' => 'Certificate',
            'field' => 'Occupational Safety',
            'institution' => 'Saudi Council for Occupational Safety, Riyadh',
            'years' => 1,
            'description' => 'Employer-funded certificate in workplace safety: manual handling, permit-to-work, personal protective equipment and incident reporting.',
        ];

        $driving = [
            'degree' => 'Certificate',
            'field' => 'Heavy Vehicle Driving',
            'institution' => 'Saudi Driving School, Riyadh',
            'years' => 1,
            'description' => 'Saudi heavy vehicle licence with defensive driving and passenger safety modules.',
        ];

        $security = [
            'degree' => 'Certificate',
            'field' => 'Security Guarding',
            'institution' => 'High Commission for Industrial Security, Riyadh',
            'years' => 1,
            'description' => 'Licensed security guard training covering access control, patrolling, radio procedure and incident reporting.',
        ];

        $hygiene = [
            'degree' => 'Certificate',
            'field' => 'Food Hygiene and HACCP',
            'institution' => 'Riyadh Municipality Training Centre',
            'years' => 1,
            'description' => 'Food hygiene certificate with HACCP principles, temperature control and allergen handling.',
        ];

        $trade = [
            'degree' => 'Vocational Certificate',
            'field' => 'Refrigeration and Air Conditioning',
            'institution' => 'Technical Training Institute, Lahore',
            'years' => 2,
            'description' => 'Two-year trade certificate in refrigeration and air conditioning with a workshop apprenticeship.',
        ];

        $electrical = [
            'degree' => 'Diploma',
            'field' => 'Electrical Installation',
            'institution' => 'Industrial Training Institute, Kochi',
            'years' => 2,
            'description' => 'Trade diploma in electrical installation and maintenance, covering wiring regulations and safe isolation.',
        ];

        return [
            'label' => 'Operations',
            'posting' => 'school-bus-driver',
            'posting_alt' => 'secondary-english-teacher',
            'max_educations' => 2,
            'people' => [
                ['Shahidul', 'Islam', 'BD'],
                ['Muhammad', 'Ashraf', 'PK'],
                ['Ravi', 'Chandran', 'IN'],
                ['Danilo', 'Cruz', 'PH'],
                ['Bishal', 'Tamang', 'NP'],
                ['Adam', 'Yousif', 'SD'],
                ['Sunil', 'Kumar', 'IN'],
                ['Jamal', 'Uddin', 'BD'],
                ['Imran', 'Baig', 'PK'],
                ['Rodel', 'Mendoza', 'PH'],
                ['Saad', 'Al Otaibi', 'SA'],
                ['Ali', 'Al Sharif', 'YE'],
                ['Mansour', 'Al Dawsari', 'SA'],
            ],
            'roles' => [
                [
                    'title' => 'School Bus Driver',
                    'employer' => 'Al Mutlaq Transport, Riyadh',
                    'duty' => 'Drove a thirty-seat school bus on a fixed Riyadh route twice a day for six years, forty-two pupils per run, with a clean licence and no reportable incident.',
                    'skills' => ['Saudi heavy vehicle licence', 'Defensive driving', 'Pre-trip vehicle checks'],
                    'education' => $driving,
                ],
                [
                    'title' => 'Heavy Vehicle Driver',
                    'employer' => 'Nesma Trading, Riyadh',
                    'duty' => 'Held a Saudi heavy vehicle licence and completed daily pre-trip checks, fuel logs and a monthly defect report to the transport supervisor.',
                    'skills' => ['Pre-trip vehicle checks', 'Defect reporting', 'Saudi heavy vehicle licence'],
                    'education' => $driving,
                ],
                [
                    'title' => 'Security Guard',
                    'employer' => 'Saudi Facilities Management Company, Riyadh',
                    'duty' => 'Manned the main gate on a twelve-hour rotating shift, checking visitor identification, logging deliveries and patrolling the perimeter hourly.',
                    'skills' => ['Access control', 'Patrolling and incident reporting', 'Shift working'],
                    'education' => $security,
                ],
                [
                    'title' => 'Shift Supervisor (Security)',
                    'employer' => 'Saudi Facilities Management Company, Riyadh',
                    'duty' => 'Monitored 120 CCTV channels from the control room, wrote the shift incident log and ran evacuation drills with the safety officer twice a term.',
                    'skills' => ['CCTV monitoring', 'Patrolling and incident reporting', 'Evacuation drills'],
                    'education' => $security,
                ],
                [
                    'title' => 'Cleaner',
                    'employer' => 'Al Majal Service Master, Riyadh',
                    'duty' => 'Cleaned twenty-four classrooms, four laboratories and eight washrooms daily to a checklist, mixing chemicals to the dilution chart and using colour-coded equipment.',
                    'skills' => ['COSHH chemical handling', 'Colour-coded cleaning', 'Floor machine operation'],
                ],
                [
                    'title' => 'Head of Housekeeping',
                    'employer' => 'Al Majal Service Master, Riyadh',
                    'duty' => 'Led a housekeeping team of nine over two shifts, set the deep-clean rota for the summer break and controlled the consumables store.',
                    'skills' => ['Team supervision', 'Colour-coded cleaning', 'Stock control'],
                ],
                [
                    'title' => 'Maintenance Technician',
                    'employer' => 'Zahran Operation and Maintenance, Riyadh',
                    'duty' => 'Carried out planned and reactive maintenance across a 14,000 square metre campus — plumbing, carpentry, door furniture and minor electrical — closing forty work orders a week on the CAFM system.',
                    'skills' => ['Planned preventive maintenance', 'CAFM work orders', 'Plumbing repairs'],
                ],
                [
                    'title' => 'HVAC Technician',
                    'employer' => 'Zahran Operation and Maintenance, Riyadh',
                    'duty' => 'Serviced sixty split units and two chiller plants: filter changes, gas top-up, coil cleaning and a quarterly planned maintenance schedule.',
                    'skills' => ['Split unit servicing', 'Planned preventive maintenance', 'Permit to work'],
                    'education' => $trade,
                ],
                [
                    'title' => 'Cook',
                    'employer' => 'Riyadh Catering Company',
                    'duty' => 'Prepared and served 700 hot meals a day in a school kitchen, working to HACCP temperature records, allergen matrices and a municipal health inspection.',
                    'skills' => ['HACCP', 'Food hygiene', 'Allergen control'],
                    'education' => $hygiene,
                ],
                [
                    'title' => 'Catering Assistant',
                    'employer' => 'Riyadh Catering Company',
                    'duty' => 'Held a food-handler certificate and ran the servery line at break and lunch, controlling portioning, queue flow and end-of-service cleaning down.',
                    'skills' => ['Food hygiene', 'Portion control', 'Colour-coded cleaning'],
                    'education' => $hygiene,
                ],
                [
                    'title' => 'Gardener',
                    'employer' => 'Al Majal Service Master, Riyadh',
                    'duty' => 'Maintained irrigation, lawns and 300 metres of planted boundary, on a seasonal pruning and fertiliser schedule with daily dripper checks.',
                    'skills' => ['Irrigation systems', 'Grounds maintenance', 'Seasonal planting'],
                ],
                [
                    'title' => 'Storekeeper',
                    'employer' => 'Nesma Trading, Riyadh',
                    'duty' => 'Ran the central store: goods-in inspection, stock counts on 400 lines, issue notes to departments and a monthly reorder report.',
                    'skills' => ['Stock control', 'Goods-in inspection', 'Reorder reporting'],
                ],
                [
                    'title' => 'Electrician',
                    'employer' => 'Zahran Operation and Maintenance, Riyadh',
                    'duty' => 'Rewired two laboratories and replaced 180 light fittings with LED panels, working to a permit-to-work system and testing to the wiring regulations.',
                    'skills' => ['Electrical safe isolation', 'Permit to work', 'Planned preventive maintenance'],
                    'education' => $electrical,
                ],
            ],
            'educations' => [$secondary, $safety, $driving, $hygiene, $security, $trade],
            'skills' => [
                'First aid', 'Health and safety awareness', 'PPE compliance', 'Shift working',
                'Time keeping', 'Manual handling', 'Team working', 'Radio procedure',
                'Stock control', 'Permit to work',
            ],
        ];
    }
}
