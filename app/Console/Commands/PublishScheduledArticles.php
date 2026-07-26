<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\States\Article\Published;
use App\States\Article\Scheduled;
use Illuminate\Console\Command;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

/**
 * Flip scheduled articles live once their published_at has passed.
 *
 * Not a pruning sweep, so CLAUDE.md's "pruning is Prunable + model:prune, never
 * a bespoke command" does not apply. It is a state transition on live rows, and
 * it must fire model events so ArticleObserver writes the audit row: a builder
 * update() would be one query and would tell nobody anything.
 *
 * Console runs have no causer, so the activity row's causer is null — exactly as
 * AppServiceProvider's CauserResolver documents.
 */
class PublishScheduledArticles extends Command
{
    protected $signature = 'articles:publish-scheduled';

    protected $description = 'Publish scheduled articles whose published_at has passed';

    public function handle(): int
    {
        $published = 0;

        // The ['status', 'published_at'] composite index serves this predicate,
        // and cursor() streams rather than hydrating every due row at once.
        $due = Article::query()
            ->whereState('status', Scheduled::class)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at');

        foreach ($due->cursor() as $article) {
            try {
                $article->status->transitionTo(Published::class);
                $published++;
            } catch (TransitionNotFound) {
                $this->warn("Skipped {$article->slug}: {$article->status->getValue()} cannot become published.");
            }
        }

        $this->info("Published {$published} scheduled article(s).");

        return self::SUCCESS;
    }
}
