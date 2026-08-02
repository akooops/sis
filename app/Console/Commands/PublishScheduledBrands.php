<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\States\Brand\Published;
use App\States\Brand\Scheduled;
use Illuminate\Console\Command;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

/**
 * Flip scheduled brands live once published_at has passed.
 *
 * Not a pruning sweep, so the "Prunable + model:prune, never a bespoke command"
 * rule does not apply — this is a state transition that must fire model events so
 * BrandObserver writes the audit row. A builder update would tell nobody anything.
 *
 * Console runs have no causer, so the activity row's causer is null.
 */
class PublishScheduledBrands extends Command
{
    protected $signature = 'brands:publish-scheduled';

    protected $description = 'Publish scheduled brands whose published_at has passed';

    public function handle(): int
    {
        $published = 0;

        // The (status, published_at) index serves this; cursor() streams the rows.
        $due = Brand::query()
            ->whereState('status', Scheduled::class)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at');

        foreach ($due->cursor() as $brand) {
            try {
                $brand->status->transitionTo(Published::class);
                $published++;
            } catch (TransitionNotFound) {
                $this->warn("Skipped {$brand->slug}: {$brand->status->getValue()} cannot become published.");
            }
        }

        $this->info("Published {$published} scheduled brand(s).");

        return self::SUCCESS;
    }
}
