<?php

namespace App\Console\Commands;

use App\Models\JobOffer;
use App\States\JobOffer\Published;
use App\States\JobOffer\Scheduled;
use Illuminate\Console\Command;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

/**
 * Flip scheduled job offers live once published_at has passed.
 *
 * Not a pruning sweep, so the "Prunable + model:prune, never a bespoke command"
 * rule does not apply — this is a state transition that must fire model events so
 * JobOfferObserver writes the audit row. A builder update would tell nobody anything.
 *
 * Console runs have no causer, so the activity row's causer is null.
 *
 * Deliberately blind to deadline_at: status is editorial, the deadline is
 * operational, and closure is derived at read time by JobOffer::scopeOpen().
 */
class PublishScheduledJobOffers extends Command
{
    protected $signature = 'job-offers:publish-scheduled';

    protected $description = 'Publish scheduled job offers whose published_at has passed';

    public function handle(): int
    {
        $published = 0;

        $due = JobOffer::query()
            ->whereState('status', Scheduled::class)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at');

        foreach ($due->cursor() as $jobOffer) {
            try {
                $jobOffer->status->transitionTo(Published::class);
                $published++;
            } catch (TransitionNotFound) {
                $this->warn("Skipped {$jobOffer->slug}: {$jobOffer->status->getValue()} cannot become published.");
            }
        }

        $this->info("Published {$published} scheduled job offer(s).");

        return self::SUCCESS;
    }
}
