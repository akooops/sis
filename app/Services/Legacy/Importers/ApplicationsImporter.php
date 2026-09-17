<?php

namespace App\Services\Legacy\Importers;

use App\Models\Candidate;
use App\Models\CandidateEducation;
use App\Models\CandidateExperience;
use App\Models\CandidateLanguage;
use App\Models\CandidateSkill;
use App\Models\Country;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\Media;
use App\Services\Legacy\LegacyImporter;
use Illuminate\Support\Facades\DB;

/**
 * Legacy job applications → Candidate + profile + JobApplication.
 *
 * ── WHY THIS DOES NOT GO THROUGH THE PROJECTOR ───────────────────────────────
 *
 * The obvious move is to synthesise a FormSubmission per legacy application and
 * let App\Services\Jobs\ApplicationProjector do the rest — it already knows this
 * shape. Two reasons not to. The old rows never WERE form submissions: they came
 * from a bespoke controller with no token, no telemetry and no answers to store,
 * so the submission would be a fiction invented to satisfy a seam. And the
 * projector dispatches an embedding, a summary and a scoring pass per candidate;
 * on an install with three thousand applications that is three thousand paid API
 * calls fired by a migration nobody expected to spend money.
 *
 * So the rows are written directly, and this importer copies the projector's
 * RULES rather than calling it:
 *
 *  - A candidate is matched on EMAIL OR PHONE, through the same
 *    Candidate::scopeIdentifiedBy the projector uses, so an applicant who
 *    applied three times is one person here.
 *  - The profile children are REPLACED WHOLESALE, not appended, because a repeat
 *    row has no stable id to diff against. Rows are walked in legacy id order, so
 *    the most recent application wins — which is the projector's own rule.
 *  - An application is keyed on (posting, candidate) and `applied_at` is only
 *    ever set once, so a re-run cannot make an old application look new and jump
 *    the queue.
 *
 * `--with-ai` is what turns the embedding and scoring back on afterwards; see
 * the command.
 *
 * The old AI columns (`ai_score`, its explanation and status) are NOT carried.
 * This app scores a (candidate × posting) PAIR into `candidate_matches` with a
 * model and a prompt that produce a different number on a different scale; a
 * legacy score copied into that table would look like a judgement this system
 * made and would be compared against ones it did.
 */
class ApplicationsImporter extends LegacyImporter
{
    /**
     * The collection an imported CV is filed under on its candidate.
     *
     * Not a constant on Candidate, because live CVs are owned by the
     * FormSubmission and the model has no media collections of its own — this is
     * a fact about the import, so it lives with the import.
     */
    public const CV_COLLECTION = 'cv';

    /** lowercase name/demonym/code => ISO code, built once. */
    protected ?array $countries = null;

    /** Free-text nationality => how many applications used it, for one summary. */
    protected array $unresolved = [];

    public function module(): string
    {
        return 'applications';
    }

    public function describe(): string
    {
        return 'Job applications → candidates, profiles and applications';
    }

    public function dependsOn(): array
    {
        return ['files', 'jobs'];
    }

    public function sources(): array
    {
        return ['job_applications'];
    }

    public function run(): void
    {
        $this->reportDropped('job_applications');

        $this->each('job_applications', function (object $row) {
            $offer = $this->offer($row);

            if (! $offer) {
                $this->c->warn("Application #{$row->id} is for a posting that was not imported — skipped.");
                $this->c->skipped();

                return;
            }

            if ($this->c->dryRun) {
                $this->c->created();

                return;
            }

            DB::transaction(function () use ($row, $offer) {
                $candidate = $this->candidate($row);

                $this->educations($row, $candidate);
                $this->experiences($row, $candidate);
                $this->languages($row, $candidate);
                $this->skills($row, $candidate);

                $application = JobApplication::firstOrNew([
                    'job_offer_id' => $offer->id,
                    'candidate_id' => $candidate->id,
                ]);

                // The old app tracked no application status — a row was simply an
                // application that had arrived, which is what `received` means.
                $application->status ??= 'received';
                $application->applied_at ??= $row->created_at ?? now();

                // Same fold as the candidate: one person applying to one posting
                // several times is ONE application row here.
                $this->earliest($application, $row->created_at ?? null);

                $this->save($application, 'job_applications', (int) $row->id);
            });
        });

        $this->reportUnresolved();
    }

    /** The posting applied to, or the seeded general one for a spontaneous application. */
    protected function offer(object $row): ?JobOffer
    {
        $id = $this->c->map->find('job_postings', $row->job_posting_id ?? null);

        return $id ? JobOffer::find($id) : JobOffer::general();
    }

    /** The person, found on email or phone, or created. */
    protected function candidate(object $row): Candidate
    {
        $email = trim((string) $row->email);
        $phone = $this->phone($row->phone ?? null);

        $candidate = Candidate::query()->identifiedBy($email, $phone)->first() ?? new Candidate;

        // Name and address follow the LATEST application — the profile is a
        // projection of the most recent one, which is the projector's own rule.
        $candidate->first_name = trim((string) $row->first_name) ?: 'Unknown';
        $candidate->last_name = trim((string) $row->last_name);
        $candidate->address = $row->address ?: $candidate->address;

        /*
         * Identity does NOT. Both columns are unique while identifiedBy() matches
         * on either, so overwriting an email on a row that was found by phone
         * takes an address another candidate already owns — which is a
         * duplicate-key violation, not a merge. See identify().
         */
        $this->identify($candidate, 'email', $email);
        $this->identify($candidate, 'phone', $phone);

        if ($code = $this->country($row->nationality ?? null)) {
            $candidate->country_id = Country::where('code', $code)->value('id') ?? $candidate->country_id;
        }

        $cv = $this->cv($row);

        if ($cv) {
            $candidate->cv_media_id = $cv;
        }

        // Earliest application wins the candidate's created_at: several legacy
        // rows fold onto one person, so assigning would let them take turns
        // rewriting it on every run.
        $this->earliest($candidate, $row->created_at ?? null);

        $candidate->save();

        $this->claimCv($candidate, $cv ?? null);

        // Mapped so a re-run finds the same person even if their email is edited.
        $this->c->map->put('job_applications_candidate', (int) $row->id, $candidate);

        return $candidate;
    }

    /**
     * GIVE THE CV AN OWNER, OR THE NIGHTLY PRUNE DELETES IT.
     *
     * `Media::prunable()` sweeps private-disk media with `model_id IS NULL`
     * older than config('uploads.prune_private_after_days') — seven days — and
     * `model:prune` runs daily. That is right for a real abandoned upload: a
     * visitor started a form, attached a CV and walked away.
     *
     * Live, a submitted CV is never caught by it, because the file is OWNED by
     * the FormSubmission (ANSWERS_COLLECTION) and `candidates.cv_media_id` is
     * only a pointer at it — the projector's own docblock says so. THIS IMPORT
     * WRITES NO FORM SUBMISSION for a job application, deliberately, so nothing
     * owned the imported CVs and every one of them was prunable on arrival:
     * 3,762 of 3,909 on this install, with legacy timestamps years past the
     * threshold. The first nightly prune would have deleted the files and the
     * rows, leaving `cv_media_id` pointing at nothing.
     *
     * So the candidate takes ownership. Every application's CV is claimed, not
     * just the newest, because an earlier CV is history rather than rubbish and
     * this import does not delete what it did not create.
     *
     * Written as plain columns rather than through UploadService::attach(),
     * because Candidate does not use HasMedia — it reaches its CV by foreign key
     * — and attach() calls singleFileCollections() on the owner.
     */
    protected function claimCv(Candidate $candidate, ?string $mediaId): void
    {
        if ($mediaId === null) {
            return;
        }

        $media = Media::find($mediaId);

        // Never steal one that already has an owner — on a re-run that owner is
        // this same candidate, and the guard is what keeps the run idempotent.
        if (! $media || $media->model_id !== null) {
            return;
        }

        $media->model_type = $candidate->getMorphClass();
        $media->model_id = $candidate->getKey();
        $media->collection_name = self::CV_COLLECTION;
        $media->save();
    }

    /** The CV media id, now on the private disk — see LegacyFiles::TARGETS. */
    protected function cv(object $row): ?string
    {
        if (! $this->c->db->has('files')) {
            return null;
        }

        $file = $this->c->db->table('files')
            ->where('model_type', 'App\Models\JobApplication')
            ->where('model_id', $row->id)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->first(['id']);

        return $file ? $this->c->map->find('files', (int) $file->id) : null;
    }

    protected function educations(object $row, Candidate $candidate): void
    {
        $rows = $this->children('job_application_education', $row->id);

        if ($rows->isEmpty()) {
            return;
        }

        CandidateEducation::where('candidate_id', $candidate->id)->delete();

        foreach ($rows as $order => $item) {
            CandidateEducation::create([
                'candidate_id' => $candidate->id,
                'institution' => $item->institution ?: 'Unknown',
                'degree' => $item->degree ?: null,
                'field_of_study' => $item->field_of_study ?: null,
                'start_year' => $this->year($item->start_year ?? null),
                'end_year' => $this->year($item->end_year ?? null),
                'description' => $item->description ?: null,
                'order' => $order,
            ]);
        }
    }

    protected function experiences(object $row, Candidate $candidate): void
    {
        $rows = $this->children('job_application_experiences', $row->id);

        if ($rows->isEmpty()) {
            return;
        }

        CandidateExperience::where('candidate_id', $candidate->id)->delete();

        foreach ($rows as $order => $item) {
            CandidateExperience::create([
                'candidate_id' => $candidate->id,
                'company_name' => $item->company_name ?: 'Unknown',
                'job_title' => $item->job_title ?: null,
                'start_year' => $this->year($item->start_year ?? null),
                'end_year' => $this->year($item->end_year ?? null),
                'is_current' => (bool) ($item->is_current ?? false),
                'description' => $item->description ?: null,
                'order' => $order,
            ]);
        }
    }

    protected function languages(object $row, Candidate $candidate): void
    {
        $rows = $this->children('job_application_languages', $row->id);

        if ($rows->isEmpty()) {
            return;
        }

        CandidateLanguage::where('candidate_id', $candidate->id)->delete();

        foreach ($rows as $order => $item) {
            CandidateLanguage::create([
                'candidate_id' => $candidate->id,
                'name' => $item->name ?: 'Unknown',
                'proficiency' => $item->proficiency ?: null,
                'order' => $order,
            ]);
        }
    }

    /**
     * The comma-separated `skills` text column, as rows.
     *
     * `fold` is the lowercased comparison form the unique index uses, produced by
     * the model's own fold() so "IB" and "ib" are one skill here exactly as they
     * are everywhere else.
     */
    protected function skills(object $row, Candidate $candidate): void
    {
        $raw = trim((string) ($row->skills ?? ''));

        if ($raw === '') {
            return;
        }

        CandidateSkill::where('candidate_id', $candidate->id)->delete();

        $seen = [];

        foreach (preg_split('/[,;\n]+/', $raw) ?: [] as $name) {
            $name = trim($name);
            $fold = CandidateSkill::fold($name);

            if ($name === '' || isset($seen[$fold])) {
                continue;
            }

            $seen[$fold] = true;

            CandidateSkill::create([
                'candidate_id' => $candidate->id,
                'name' => $name,
                'fold' => $fold,
            ]);
        }
    }

    /** One legacy application's child rows, in their own order. */
    protected function children(string $table, int|string $applicationId)
    {
        if (! $this->c->db->has($table)) {
            return collect();
        }

        return $this->c->db->table($table)
            ->where('job_application_id', $applicationId)
            ->orderBy('id')
            ->get();
    }

    /** A four-digit year, or null — the old columns were plain integers. */
    protected function year(mixed $value): ?int
    {
        $year = (int) $value;

        return $year >= 1900 && $year <= 2100 ? $year : null;
    }

    /**
     * The old free-text nationality, resolved to an ISO code.
     *
     * The old column was a plain string and, depending on which version of that
     * admin form wrote it, holds a code, an English country name or a demonym in
     * English or Arabic. All four are matched, against every locale, because the
     * new `candidates.country_id` is a real foreign key and the scorer reasons
     * about work authorisation from it — a nationality silently dropped is a
     * candidate quietly mis-scored.
     *
     * Built ONCE into a lookup rather than per row: 249 countries × nine locales
     * × three name columns, against a table of applications that is unbounded.
     */
    protected function country(?string $answer): ?string
    {
        $needle = $this->foldName((string) $answer);

        if ($needle === '') {
            return null;
        }

        if ($this->countries === null) {
            $this->countries = [];

            // The hand-written corrections first, so an alias always wins over a
            // coincidental match in the table.
            foreach (config('legacy.nationalities', []) as $alias => $code) {
                $this->countries[$this->foldName((string) $alias)] = strtoupper($code);
            }

            foreach (Country::query()->get(['code', 'name', 'title', 'nationality']) as $country) {
                $names = array_merge(
                    [$country->code, $country->name],
                    array_values($country->getTranslations('title')),
                    array_values($country->getTranslations('nationality')),
                );

                foreach ($names as $name) {
                    $key = $this->foldName((string) $name);

                    // FIRST WRITER WINS. Demonyms are not unique across ISO rows
                    // — three are "British" — and the earlier row is the country
                    // rather than one of its territories.
                    if ($key !== '' && ! isset($this->countries[$key])) {
                        $this->countries[$key] = $country->code;
                    }
                }
            }
        }

        $code = $this->countries[$needle] ?? null;

        if (! $code) {
            /*
             * COUNTED, NOT WARNED PER ROW. A nationality is free text on a table
             * with no upper bound — a real install has 3,900 applications over 66
             * distinct spellings — so warning per candidate would print thousands
             * of lines saying the same 66 things and bury every other finding in
             * the report. Summarised once in run() instead.
             */
            $this->unresolved[trim((string) $answer)] = ($this->unresolved[trim((string) $answer)] ?? 0) + 1;
        }

        return $code;
    }

    /**
     * The distinct nationalities nothing matched, worst first.
     *
     * Worth naming rather than totalling: the fix is usually a spelling the
     * countries table happens not to carry, and an admin can only act on it if
     * they can see which one.
     */
    protected function reportUnresolved(): void
    {
        if ($this->unresolved === []) {
            return;
        }

        arsort($this->unresolved);

        $sample = [];

        foreach (array_slice($this->unresolved, 0, 8, true) as $value => $count) {
            $sample[] = ($value === '' ? '(blank)' : $value)." ({$count})";
        }

        $this->c->note(
            'Unmatched nationalities can be corrected one line at a time in '
            ."config('legacy.nationalities'), then re-run — the import is idempotent."
        );

        $this->c->warn(
            count($this->unresolved).' nationality value(s) matched no country and were left unset on those '
            .'candidates, affecting '.array_sum($this->unresolved).' application(s): '.implode(', ', $sample)
            .(count($this->unresolved) > 8 ? ', …' : '').'. The scorer reasons about work authorisation from this, '
            .'so a gap is a candidate mis-scored rather than merely a blank field.'
        );
    }

    /**
     * The comparison form of a country name or demonym.
     *
     * Lowercased, and ARABIC ORTHOGRAPHY NORMALISED — which is the part that
     * earns its keep on this data. Arabic writes the same word several defensible
     * ways: the alef carries a hamza or does not (`الأردن` / `الاردن`), a final
     * ta marbuta is often typed as a haa (`سعودية` / `سعوديه`), alef maqsura
     * stands in for yaa, and harakat and tatweel are decoration that carries no
     * distinction here. The countries table stores one spelling; applicants typed
     * whichever they use, and on a bilingual school's CV pile that is most of the
     * near-misses.
     *
     * Applied to BOTH sides — the stored names and the answer — so the comparison
     * stays symmetric and no alias has to be written twice.
     */
    protected function foldName(string $value): string
    {
        $value = trim(mb_strtolower($value));

        if ($value === '') {
            return '';
        }

        $value = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $value) ?? $value;

        $value = strtr($value, [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
            'ة' => 'ه',
            'ى' => 'ي',
        ]);

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }
}
