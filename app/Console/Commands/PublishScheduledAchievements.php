<?php

namespace App\Console\Commands;

use App\Models\Achievement;
use App\States\Achievement\Published;
use App\States\Achievement\Scheduled;
use Illuminate\Console\Command;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

/**
 * Flip scheduled achievements live once their published_at has passed.
 *
 * Not a pruning sweep. It is a state transition on live rows, and it must fire
 * model events so AchievementObserver writes the audit row.
 */
class PublishScheduledAchievements extends Command
{
    protected $signature = 'achievements:publish-scheduled';

    protected $description = 'Publish scheduled achievements whose published_at has passed';

    public function handle(): int
    {
        $published = 0;

        $due = Achievement::query()
            ->whereState('status', Scheduled::class)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at');

        foreach ($due->cursor() as $achievement) {
            try {
                $achievement->status->transitionTo(Published::class);
                $published++;
            } catch (TransitionNotFound) {
                $this->warn("Skipped {$achievement->slug}: {$achievement->status->getValue()} cannot become published.");
            }
        }

        $this->info("Published {$published} scheduled achievement(s).");

        return self::SUCCESS;
    }
}
