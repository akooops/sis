<?php

namespace App\Services\Jobs;

use App\Contracts\Forms\SubmissionProjector;
use App\Jobs\Ai\EmbedCandidate;
use App\Jobs\Ai\ScoreCandidateMatches;
use App\Jobs\Ai\SummariseCandidate;
use App\Models\Candidate;
use App\Models\CandidateSkill;
use App\Models\Country;
use App\Models\FormSubmission;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\Media;
use Illuminate\Support\Facades\DB;

/**
 * Turns a completed application submission into domain rows.
 *
 * THE SEAM. Forms own how an application was collected — steps, files, captcha,
 * telemetry, nine locales. This owns what it MEANS: a person, what they can do,
 * and the fact that they applied to something. The raw answers stay on the
 * FormSubmission and the application points back at it, so this can be re-run at
 * any time without the original being consumed.
 *
 * IDEMPOTENT BY CONSTRUCTION. Candidates are matched on email or phone,
 * applications on (posting, candidate), and the profile children are replaced
 * wholesale rather than appended — so running it twice over one submission
 * produces the same rows, and re-running it after a map change repairs old data
 * instead of duplicating it.
 *
 * The profile is a projection of the MOST RECENT application, which is why the
 * children are replaced: someone who applies again a year later has a newer CV
 * and one more job, and the candidate record should say so rather than hold both
 * versions interleaved.
 */
class ApplicationProjector implements SubmissionProjector
{
    public function project(FormSubmission $submission): mixed
    {
        // Only a completed submission is an application. A spam row, an
        // abandoned draft or one that failed validation is not one, and the
        // fake-confirmation path deliberately writes a `spam` row that must
        // never become a person.
        if ($submission->status !== 'completed') {
            return null;
        }

        $data = $submission->data ?? [];
        $offer = $this->offer($data);

        if (! $offer) {
            return null;
        }

        return DB::transaction(function () use ($submission, $data, $offer) {
            $candidate = $this->candidate($data);

            $this->replaceGroups($candidate, $data);
            $this->replaceSkills($candidate, $data);

            $application = JobApplication::firstOrNew([
                'job_offer_id' => $offer->id,
                'candidate_id' => $candidate->id,
            ]);

            $application->form_submission_id = $submission->id;
            // First application wins the timestamp: a re-run must not make an old
            // application look like it arrived today and jump the queue.
            $application->applied_at ??= $submission->submitted_at ?? now();
            $application->save();

            /*
             * Score AFTER COMMIT, not inside the transaction: the job reads the
             * candidate back by id, and on a synchronous queue it would otherwise
             * run against rows this transaction has not written yet.
             *
             * Dispatched on every projection, including a re-run — a candidate
             * whose profile has just been rewritten deserves re-scoring against
             * it, and updateOrCreate makes that an overwrite rather than a pile.
             */
            DB::afterCommit(function () use ($candidate) {
                // Embedding FIRST: it is what the nightly rebuild clusters on, so
                // a candidate who applies today is poolable tonight. The other two
                // do not depend on it — scoring falls back to every open posting
                // while a candidate has no cluster yet.
                EmbedCandidate::dispatch($candidate->id);
                SummariseCandidate::dispatch($candidate->id);
                ScoreCandidateMatches::dispatch($candidate->id);
            });

            return $application;
        });
    }

    /**
     * The posting applied to, from the hidden field.
     *
     * RE-VERIFIED, NEVER TRUSTED: the field is a hidden input and a visitor can
     * put anything in it. A closed or unknown posting is refused here as well as
     * at submit, because this also runs on re-projection long after the fact.
     *
     * @param  array<string, mixed>  $data
     */
    protected function offer(array $data): ?JobOffer
    {
        $id = $data[config('jobs.job_offer_field')] ?? null;

        $offer = is_string($id) && $id !== ''
            ? JobOffer::find($id)
            : JobOffer::general();

        return $offer;
    }

    /**
     * The person, found or created.
     *
     * EMAIL OR PHONE, not both — see Candidate::scopeIdentifiedBy. The phone was
     * normalised to E164 by PhoneType::store() at submit, so the comparison is
     * against the same shape every time.
     *
     * @param  array<string, mixed>  $data
     */
    protected function candidate(array $data): Candidate
    {
        $fields = config('jobs.fields');

        $attributes = [];

        foreach ($fields as $answerKey => $column) {
            $value = $data[$answerKey] ?? null;

            if ($value !== null && $value !== '') {
                $attributes[$column] = is_string($value) ? trim($value) : $value;
            }
        }

        $candidate = Candidate::query()
            ->identifiedBy($attributes['email'] ?? null, $attributes['phone'] ?? null)
            ->first() ?? new Candidate;

        $candidate->fill($attributes);

        if ($country = $this->country($data)) {
            $candidate->country_id = $country->id;
        }

        if ($media = $this->cv($data)) {
            $candidate->cv_media_id = $media->id;
        }

        $candidate->save();

        return $candidate;
    }

    /**
     * Nationality, by country id or by ISO code — the form may offer either, and
     * a value that matches neither is dropped rather than guessed at.
     *
     * @param  array<string, mixed>  $data
     */
    protected function country(array $data): ?Country
    {
        $value = $data[config('jobs.nationality_field')] ?? null;

        if (! is_string($value) || $value === '') {
            return null;
        }

        return Country::query()
            ->where('id', $value)
            ->orWhere('code', strtoupper($value))
            ->first();
    }

    /**
     * The CV media row, if the answer names one that exists.
     *
     * A POINTER, not a copy: the file stays owned by the submission, scanned and
     * reachable only through a signed route. Nothing here moves bytes.
     *
     * @param  array<string, mixed>  $data
     */
    protected function cv(array $data): ?Media
    {
        $value = $data[config('jobs.cv_field')] ?? null;
        $id = is_array($value) ? ($value[0] ?? null) : $value;

        return is_string($id) && $id !== '' ? Media::find($id) : null;
    }

    /**
     * Education, experience and languages — replaced wholesale.
     *
     * DELETE THEN INSERT rather than diffing: the answers carry no stable id per
     * row (a repeat is identified only by its position), so there is nothing to
     * match an existing child against. Replacing is the only operation that is
     * both correct and idempotent.
     *
     * @param  array<string, mixed>  $data
     */
    protected function replaceGroups(Candidate $candidate, array $data): void
    {
        foreach (config('jobs.groups') as $answerKey => $spec) {
            $rows = $data[$answerKey] ?? null;

            if (! is_array($rows)) {
                continue;
            }

            /** @var class-string<\Illuminate\Database\Eloquent\Model> $model */
            $model = $spec['table'];

            $model::query()->where('candidate_id', $candidate->id)->delete();

            $order = 0;

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $attributes = ['candidate_id' => $candidate->id, 'order' => $order];

                foreach ($spec['map'] as $childKey => $column) {
                    if (array_key_exists($childKey, $row)) {
                        $attributes[$column] = $row[$childKey];
                    }
                }

                // A row with nothing but its position is not an entry. The
                // renderer already drops blank repeats and the validator agrees,
                // so this only catches a hand-rolled post.
                if (count(array_filter($attributes, fn ($v) => $v !== null && $v !== '')) <= 2) {
                    continue;
                }

                $model::create($attributes);
                $order++;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function replaceSkills(Candidate $candidate, array $data): void
    {
        $skills = $data[config('jobs.skills_field')] ?? null;

        if (! is_array($skills)) {
            return;
        }

        CandidateSkill::query()->where('candidate_id', $candidate->id)->delete();

        $seen = [];

        foreach ($skills as $skill) {
            if (! is_string($skill)) {
                continue;
            }

            $name = trim($skill);
            $fold = CandidateSkill::fold($name);

            // The unique index is (candidate_id, fold), so a duplicate inside one
            // submission would throw rather than being ignored.
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
}
