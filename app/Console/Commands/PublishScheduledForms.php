<?php

namespace App\Console\Commands;

use App\Models\Form;
use App\States\Form\Published;
use App\States\Form\Scheduled;
use Illuminate\Console\Command;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

/**
 * Flip scheduled forms live once published_at has passed.
 *
 * Not a pruning sweep, so the "Prunable + model:prune, never a bespoke command"
 * rule does not apply — this is a state transition that must fire model events so
 * FormObserver writes the audit row. A builder update would tell nobody anything.
 *
 * Console runs have no causer, so the activity row's causer is null.
 */
class PublishScheduledForms extends Command
{
    protected $signature = 'forms:publish-scheduled';

    protected $description = 'Publish scheduled forms whose published_at has passed';

    public function handle(): int
    {
        $published = 0;

        // The (status, published_at) index serves this; cursor() streams the rows.
        $due = Form::query()
            ->whereState('status', Scheduled::class)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at');

        foreach ($due->cursor() as $form) {
            try {
                $form->status->transitionTo(Published::class);
                $published++;
            } catch (TransitionNotFound) {
                $this->warn("Skipped {$form->slug}: {$form->status->getValue()} cannot become published.");
            }
        }

        $this->info("Published {$published} scheduled form(s).");

        return self::SUCCESS;
    }
}
