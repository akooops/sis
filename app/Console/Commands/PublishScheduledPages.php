<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\States\Page\Published;
use App\States\Page\Scheduled;
use Illuminate\Console\Command;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

/**
 * Flip scheduled pages live once published_at has passed.
 *
 * Not a pruning sweep, so the "Prunable + model:prune, never a bespoke command"
 * rule does not apply — this is a state transition that must fire model events so
 * PageObserver writes the audit row. A builder update would tell nobody anything.
 *
 * Console runs have no causer, so the activity row's causer is null.
 */
class PublishScheduledPages extends Command
{
    protected $signature = 'pages:publish-scheduled';

    protected $description = 'Publish scheduled pages whose published_at has passed';

    public function handle(): int
    {
        $published = 0;

        $due = Page::query()
            ->whereState('status', Scheduled::class)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at');

        foreach ($due->cursor() as $page) {
            try {
                $page->status->transitionTo(Published::class);
                $published++;
            } catch (TransitionNotFound) {
                $this->warn("Skipped {$page->slug}: {$page->status->getValue()} cannot become published.");
            }
        }

        $this->info("Published {$published} scheduled page(s).");

        return self::SUCCESS;
    }
}
