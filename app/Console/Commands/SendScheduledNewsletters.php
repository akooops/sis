<?php

namespace App\Console\Commands;

use App\Jobs\ShipNewsletter;
use App\Models\Newsletter;
use App\Models\NewsletterGroupSubscriber;
use App\States\Newsletter\Failed;
use App\States\Newsletter\Scheduled;
use App\States\Newsletter\Sending;
use App\States\Newsletter\Sent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Throwable;

/**
 * Send scheduled newsletters whose scheduled_at has passed. This is the ONLY way
 * a newsletter ever goes out — there is no send-now endpoint, so "send now" is a
 * scheduled newsletter dated now that this command picks up on its next tick.
 *
 * Not a pruning sweep, so the "Prunable + model:prune, never a bespoke command"
 * rule does not apply — these are state transitions that must fire model events so
 * NewsletterObserver writes the audit rows. A builder update would tell nobody.
 *
 * Console runs have no causer, so the activity rows' causer is null.
 */
class SendScheduledNewsletters extends Command
{
    protected $signature = 'newsletters:send-scheduled';

    protected $description = 'Send scheduled newsletters whose scheduled_at has passed';

    public function handle(): int
    {
        $sent = 0;

        // The (status, scheduled_at) index serves this; cursor() streams the rows.
        $due = Newsletter::query()
            ->whereState('status', Scheduled::class)
            // A publish-only issue is never emailed, whatever its status says.
            ->where('is_sendable', true)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at');

        foreach ($due->cursor() as $newsletter) {
            try {
                $newsletter->status->transitionTo(Sending::class);
            } catch (TransitionNotFound) {
                $this->warn("Skipped {$newsletter->name}: {$newsletter->status->getValue()} cannot become sending.");

                continue;
            }

            try {
                $dispatched = $this->dispatchFor($newsletter);
            } catch (Throwable $e) {
                // Never leave a claimed newsletter in sending — it has no way out.
                $this->markFailed($newsletter, $e->getMessage());

                continue;
            }

            if ($dispatched === 0) {
                $this->markFailed($newsletter);

                continue;
            }

            $newsletter->status->transitionTo(Sent::class);
            $newsletter->sent_at = now();
            $newsletter->save();

            $sent++;
            $this->info("Queued {$dispatched} recipient(s) for {$newsletter->name}.");
        }

        $this->info("Sent {$sent} scheduled newsletter(s).");

        return self::SUCCESS;
    }

    /**
     * One job per active subscriber across every targeted group, de-duplicated by
     * email: someone on two of the lists must receive this once, not twice.
     *
     * Chunked rather than ->get() because a list is as big as the audience. The
     * $seen map still holds one key per distinct address — that is the dedup, and
     * it is addresses, not models.
     */
    protected function dispatchFor(Newsletter $newsletter): int
    {
        $groupIds = $newsletter->groups()->pluck('newsletter_groups.id')->all();

        if ($groupIds === []) {
            return 0;
        }

        $seen = [];
        $dispatched = 0;

        NewsletterGroupSubscriber::query()
            ->whereIn('newsletter_group_id', $groupIds)
            ->where('is_active', true)
            ->select(['id', 'email'])
            ->chunkById(500, function ($subscribers) use ($newsletter, &$seen, &$dispatched) {
                foreach ($subscribers as $subscriber) {
                    $email = mb_strtolower(trim((string) $subscriber->email));

                    if ($email === '' || isset($seen[$email])) {
                        continue;
                    }

                    $seen[$email] = true;

                    // On the sync queue a dispatch IS the send, so one unreachable
                    // provider would abort the run and strand the newsletter in
                    // sending. Log the recipient and carry on.
                    try {
                        ShipNewsletter::dispatch($newsletter->id, $subscriber->id, $newsletter->integration_id);
                        $dispatched++;
                    } catch (Throwable $e) {
                        Log::channel('integrations')->error('newsletter.dispatch-failed', [
                            'newsletter_id' => $newsletter->id,
                            'subscriber_id' => $subscriber->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $dispatched;
    }

    /** No recipients, or the run blew up — marking it Sent would be a lie. */
    protected function markFailed(Newsletter $newsletter, ?string $reason = null): void
    {
        $newsletter->status->transitionTo(Failed::class);

        Log::channel('integrations')->warning('newsletter.failed', [
            'newsletter_id' => $newsletter->id,
            'reason' => $reason ?? 'no active subscriber in its groups',
        ]);

        $this->warn("Failed {$newsletter->name}: ".($reason ?? 'no active subscriber in its groups').'.');
    }
}
