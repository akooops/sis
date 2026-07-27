<?php

namespace App\Console\Commands;

use App\Models\Album;
use App\States\Album\Published;
use App\States\Album\Scheduled;
use Illuminate\Console\Command;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

/**
 * Flip scheduled albums live once published_at has passed.
 *
 * Not a pruning sweep, so the "Prunable + model:prune, never a bespoke command"
 * rule does not apply — this is a state transition that must fire model events so
 * AlbumObserver writes the audit row. A builder update would tell nobody anything.
 *
 * Console runs have no causer, so the activity row's causer is null.
 */
class PublishScheduledAlbums extends Command
{
    protected $signature = 'albums:publish-scheduled';

    protected $description = 'Publish scheduled albums whose published_at has passed';

    public function handle(): int
    {
        $published = 0;

        // The (status, published_at) index serves this; cursor() streams the rows.
        $due = Album::query()
            ->whereState('status', Scheduled::class)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at');

        foreach ($due->cursor() as $album) {
            try {
                $album->status->transitionTo(Published::class);
                $published++;
            } catch (TransitionNotFound) {
                $this->warn("Skipped {$album->slug}: {$album->status->getValue()} cannot become published.");
            }
        }

        $this->info("Published {$published} scheduled album(s).");

        return self::SUCCESS;
    }
}
