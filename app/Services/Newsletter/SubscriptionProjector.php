<?php

namespace App\Services\Newsletter;

use App\Contracts\Forms\SubmissionProjector;
use App\Models\FormSubmission;
use App\Models\NewsletterGroup;
use App\Models\NewsletterGroupSubscriber;
use Illuminate\Support\Facades\DB;

/**
 * Turns a completed signup submission into subscriber rows.
 *
 * THE SEAM, USED A FOURTH TIME — after jobs, visits and facilities. Forms own
 * how the signup was collected: the honeypot, the minimum submit time, the
 * captcha, the country and IP blocks, the per-visitor caps, nine locales. This
 * owns what it MEANS — that an address wants one or more mailing lists. The raw
 * answers stay on the FormSubmission, so this can be re-run at any time.
 *
 * ONE SUBMISSION, SEVERAL ROWS. The picker is multi-value, so one signup writes
 * one `newsletter_group_subscribers` row per list chosen — the table's unique
 * index is (newsletter_group_id, email), not email alone, precisely because one
 * address may be on several lists.
 *
 * IDEMPOTENCY IS DATED, NOT FLAGGED, and that is the subtle part. The obvious
 * "set is_active = true" is wrong twice over: re-running an old submission would
 * silently UN-UNSUBSCRIBE somebody who has since left, and never touching an
 * existing row would break the one documented way back onto a list. So the write
 * is gated on the SUBMISSION'S OWN TIMESTAMP — a row is (re)activated only when
 * it was last subscribed BEFORE this submission was made, and `subscribed_at` is
 * stamped with that instant rather than with now(). Both properties then fall
 * out:
 *
 *   - re-running the same submission finds subscribed_at == that instant, which
 *     is not `<`, so it writes nothing;
 *   - somebody who unsubscribed AFTER submitting keeps is_active = false however
 *     often this is re-run, because their subscribed_at is that same instant;
 *   - a genuine re-signup is a NEWER submission, so it reactivates and restamps.
 *
 * NO SUBMIT-TIME UNIQUENESS RULE, DELIBERATELY. An address already on a list is
 * shown the ordinary confirmation, exactly as a new one is — a
 * `config('forms.submission_rules')` entry answering "you are already
 * subscribed" would tell anyone who asks whether a given address is on the list,
 * which is the same fact NewsletterController::unsubscribe() refuses to leak by
 * answering a bogus signature and a real one identically. Because projection
 * happens after the response, that property is free here.
 */
class SubscriptionProjector implements SubmissionProjector
{
    public function project(FormSubmission $submission): mixed
    {
        // Only a completed submission is a signup. The fake-confirmation path
        // deliberately writes a `spam` row, and that must never become an address
        // on a mailing list.
        if ($submission->status !== 'completed') {
            return null;
        }

        $data = $submission->data ?? [];

        $email = trim((string) ($data[(string) config('newsletter.fields.email')] ?? ''));

        if ($email === '') {
            return null;
        }

        $groups = $this->groups($data);

        if ($groups->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($groups, $email, $submission) {
            return $groups
                ->map(fn (NewsletterGroup $group) => $this->subscribe($group, $email, $submission))
                ->all();
        });
    }

    /**
     * The lists this submission asked for.
     *
     * An unanswered multi-value field is stored as NULL, not as an empty array —
     * SubmissionValidator::normalise() decides blankness before store() is ever
     * called — so this must test the shape rather than iterate. The field is
     * seeded required, so in practice the fallback is for a hand-built request;
     * NewsletterGroup::default() exists precisely so a signup naming no list
     * still has somewhere to go.
     *
     * Unknown codes are dropped rather than failing the whole projection: the
     * membership rule already rejected them at submit, so one here means a list
     * was retired between the submission and the projection.
     *
     * @param  array<string, mixed>  $data
     * @return \Illuminate\Support\Collection<int, NewsletterGroup>
     */
    protected function groups(array $data): mixed
    {
        $codes = $data[(string) config('newsletter.fields.groups')] ?? null;

        if (! is_array($codes) || $codes === []) {
            $default = NewsletterGroup::default();

            return $default ? collect([$default]) : collect();
        }

        return NewsletterGroup::query()->whereIn('code', $codes)->get();
    }

    /**
     * One address on one list. See the class docblock for why the guard is a
     * date comparison and not a flag.
     */
    protected function subscribe(NewsletterGroup $group, string $email, FormSubmission $submission): NewsletterGroupSubscriber
    {
        $subscriber = NewsletterGroupSubscriber::firstOrNew([
            'newsletter_group_id' => $group->getKey(),
            'email' => $email,
        ]);

        $at = $this->submittedAt($submission);

        $stale = ! $subscriber->exists
            || $subscriber->subscribed_at === null
            || $subscriber->subscribed_at->lt($at);

        if ($stale) {
            $subscriber->is_active = true;
            $subscriber->subscribed_at = $at;
        }

        // A save with nothing dirty is a no-op and writes no audit row, which is
        // what makes a re-run silent rather than merely harmless.
        $subscriber->save();

        return $subscriber;
    }

    /**
     * WHEN THEY SUBSCRIBED — which is `submitted_at`, NOT `created_at`.
     *
     * A completed submission is usually not created at submit time: the
     * telemetry beacon force-flushes `start` on the visitor's FIRST INTERACTION
     * and it is that send which CREATES the draft row, which
     * SubmitController::newSubmission() then claims. So `created_at` is when
     * somebody first touched a field, up to config('forms.token_ttl') — two
     * hours — before they actually subscribed.
     *
     * Two things go wrong if the guard reads it. The stored `subscribed_at` an
     * admin reads is simply the wrong moment; and worse, a visitor who leaves one
     * tab open, subscribes from elsewhere, unsubscribes, and then submits the
     * stale tab would be refused reactivation — the older tab's draft predates
     * the stamp — while still being shown the ordinary confirmation.
     *
     * Falls back to `created_at` because nothing guarantees the column on a row
     * written by some future path, and a null here would make every comparison
     * false and silently stop reactivating anybody.
     */
    protected function submittedAt(FormSubmission $submission): mixed
    {
        return $submission->submitted_at ?? $submission->created_at;
    }
}
