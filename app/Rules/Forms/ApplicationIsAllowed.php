<?php

namespace App\Rules\Forms;

use App\Contracts\Forms\SubmissionRule;
use App\Models\Candidate;
use App\Models\Form;
use App\Models\JobApplication;
use App\Models\JobOffer;

/**
 * The two things a job application must satisfy that no single FIELD can check.
 *
 *  1. the posting exists and is still open;
 *  2. this person has not already applied to it.
 *
 * WHY RULES AND NOT A CHECK IN THE PROJECTOR. The projector runs after the
 * response has gone, so a failure there is silent: the applicant is thanked, and
 * nothing is created. As rules, each one lands under the input the applicant can
 * actually fix, in their own language, before anything is stored.
 *
 * The unique index on (job_offer_id, candidate_id) is still the real guarantee —
 * two simultaneous submits could both pass this and only one would survive the
 * insert. This exists to make the common case a readable message rather than a
 * constraint violation.
 */
class ApplicationIsAllowed implements SubmissionRule
{
    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, array<int, mixed>>
     */
    public function rules(Form $form, array $answers): array
    {
        $offerKey = config('jobs.job_offer_field');
        $emailKey = $this->keyFor('email');
        $phoneKey = $this->keyFor('phone');
        $offer = $this->offer($answers[$offerKey] ?? null);

        return [
            "fields.{$offerKey}" => [$this->postingIsOpen($offer)],
            "fields.{$emailKey}" => [$this->notAlreadyApplied($offer, $answers, $emailKey, $phoneKey)],
        ];
    }

    /**
     * The posting must exist and still accept applications.
     *
     * RE-CHECKED HERE because `job_offer_id` is a hidden input: a visitor can put
     * anything in it, including the id of a posting that closed while the form
     * sat open in a tab.
     */
    protected function postingIsOpen(?JobOffer $offer): callable
    {
        return function (string $attribute, mixed $value, callable $fail) use ($offer) {
            if (! $offer) {
                $fail(__('forms.job_offer_missing'));

                return;
            }

            if (! $offer->isOpen()) {
                $fail(__('forms.job_offer_closed'));
            }
        };
    }

    /**
     * One application per person per posting.
     *
     * The person is matched the SAME way the projector matches them — email or
     * phone, never both — or the rule would pass someone the projector then
     * recognises as an existing candidate, and the unique index would reject the
     * insert after the applicant had already been thanked.
     *
     * Attached to the EMAIL field because that is the identifier an applicant can
     * see and change; pointing it at the hidden posting id would show a message
     * with nothing under it.
     *
     * @param  array<string, mixed>  $answers
     */
    protected function notAlreadyApplied(?JobOffer $offer, array $answers, string $emailKey, string $phoneKey): callable
    {
        return function (string $attribute, mixed $value, callable $fail) use ($offer, $answers, $emailKey, $phoneKey) {
            if (! $offer) {
                return;
            }

            $candidate = Candidate::query()
                ->identifiedBy($answers[$emailKey] ?? null, $answers[$phoneKey] ?? null)
                ->first();

            if (! $candidate) {
                return;
            }

            $applied = JobApplication::query()
                ->where('job_offer_id', $offer->id)
                ->where('candidate_id', $candidate->id)
                ->exists();

            if ($applied) {
                $fail(__('forms.job_already_applied'));
            }
        };
    }

    /** The posting a submitted id names, or the always-open general one. */
    protected function offer(mixed $id): ?JobOffer
    {
        return is_string($id) && $id !== '' ? JobOffer::find($id) : JobOffer::general();
    }

    /**
     * The FORM key feeding one domain column.
     *
     * Read off config('jobs.fields') rather than hardcoded, so renaming a question
     * moves this rule with it — the map is the single statement of that
     * vocabulary, and a second copy here would be the thing that goes stale.
     */
    protected function keyFor(string $column): string
    {
        return (string) (array_search($column, config('jobs.fields', []), true) ?: $column);
    }
}
