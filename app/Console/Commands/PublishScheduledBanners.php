<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\States\Banner\Published;
use App\States\Banner\Scheduled;
use Illuminate\Console\Command;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

/**
 * Flip scheduled banners live once published_at has passed.
 *
 * Not a pruning sweep, so the "Prunable + model:prune, never a bespoke command"
 * rule does not apply — this is a state transition that must fire model events so
 * BannerObserver writes the audit row. A builder update would tell nobody anything.
 *
 * Console runs have no causer, so the activity row's causer is null.
 */
class PublishScheduledBanners extends Command
{
    protected $signature = 'banners:publish-scheduled';

    protected $description = 'Publish scheduled banners whose published_at has passed';

    public function handle(): int
    {
        $published = 0;

        // The (status, published_at) index serves this; cursor() streams the rows.
        $due = Banner::query()
            ->whereState('status', Scheduled::class)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at');

        foreach ($due->cursor() as $banner) {
            try {
                $banner->status->transitionTo(Published::class);
                $published++;
            } catch (TransitionNotFound) {
                // Banners have no slug — the internal name is what identifies one.
                $this->warn("Skipped {$banner->name}: {$banner->status->getValue()} cannot become published.");
            }
        }

        $this->info("Published {$published} scheduled banner(s).");

        return self::SUCCESS;
    }
}
