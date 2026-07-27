<?php

namespace App\States\JobOffer;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Where a job offer sits editorially. Only Published is served.
 *
 *  - Draft     : the default. Being written, never announced.
 *  - Scheduled : waiting for published_at; job-offers:publish-scheduled flips it.
 *  - Published : live. published_at records when it went live.
 *  - Hidden    : was public and has been withdrawn. published_at is kept.
 *
 * Published is reachable from everywhere so a job offer can always go back up, and
 * Hidden from anything that was public so it can always come down. Published ->
 * Draft is barred (taking a live job offer down is an explicit Hidden, worth its own
 * audit row) and so is Draft -> Hidden (a draft was never public).
 */
abstract class JobOfferStatus extends State
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
