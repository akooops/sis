<?php

namespace App\States\Article;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Where an article sits editorially. Only Published is served.
 *
 *  - Draft     : the default. Being written, never announced.
 *  - Scheduled : finished, waiting for published_at. articles:publish-scheduled
 *                flips it (App\Console\Commands\PublishScheduledArticles).
 *  - Published : live. published_at records when it went live.
 *  - Hidden    : was (or was about to be) public and has been withdrawn.
 *                published_at is kept, so re-publishing remembers the history.
 *
 * Published is reachable from every state so an article can always go back up,
 * and Hidden from every state that carries a public commitment so it can always
 * come down. Draft cannot be reached from Published — pulling a live article is an
 * explicit take-down (Hidden) worth its own audit row — and Hidden cannot be
 * reached from Draft, because a draft was never public.
 */
abstract class ArticleStatus extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, Scheduled::class)
            ->allowTransition(Draft::class, Published::class)
            ->allowTransition(Scheduled::class, Draft::class)
            ->allowTransition(Scheduled::class, Published::class)
            ->allowTransition(Scheduled::class, Hidden::class)
            ->allowTransition(Published::class, Hidden::class)
            ->allowTransition(Published::class, Scheduled::class)
            ->allowTransition(Hidden::class, Draft::class)
            ->allowTransition(Hidden::class, Scheduled::class)
            ->allowTransition(Hidden::class, Published::class);
    }
}
