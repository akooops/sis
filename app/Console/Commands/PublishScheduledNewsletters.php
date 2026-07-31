<?php

namespace App\Console\Commands;

use App\Models\Newsletter;
use App\States\NewsletterPublication\Published;
use App\States\NewsletterPublication\Scheduled;
use Illuminate\Console\Command;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

/**
 * Put scheduled newsletters in the website archive once published_at has passed.
 *
 * The website side only: it never reads or writes sent_status, and
 * newsletters:send-scheduled never touches published_status. An issue can be live
 * on the site and unsent, or sent and unlisted — the two are independent.
 *
 * Not a pruning sweep, so the "Prunable + model:prune, never a bespoke command"
 * rule does not apply — this is a state transition that must fire model events so
 * NewsletterObserver writes the audit row. A builder update would tell nobody.
 *
 * Console runs have no causer, so the activity row's causer is null.
 */
class PublishScheduledNewsletters extends Command
{
    protected $signature = 'newsletters:publish-scheduled';

    protected $description = 'Publish scheduled newsletters whose published_at has passed';

    public function handle(): int
    {
        $published = 0;

        // The (published_status, published_at) index serves this; cursor() streams the rows.
        $due = Newsletter::query()
            ->whereState('published_status', Scheduled::class)
            // A send-only issue is never listed, whatever its published_status says.
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at');

        foreach ($due->cursor() as $newsletter) {
            try {
                // published_at is left alone: the scheduled moment IS when it went live.
                $newsletter->published_status->transitionTo(Published::class);
                $published++;
            } catch (TransitionNotFound) {
                $this->warn("Skipped {$newsletter->name}: {$newsletter->published_status->getValue()} cannot become published.");
            }
        }

        $this->info("Published {$published} scheduled newsletter(s).");

        return self::SUCCESS;
    }
}
