<?php

namespace App\States\NewsletterPublication;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Where an issue sits on the website archive. Only Published is served.
 *
 *  - Draft     : the default. Being prepared, never listed.
 *  - Scheduled : waiting for published_at; newsletters:publish-scheduled flips it.
 *  - Published : live. published_at records when it went live.
 *  - Hidden    : was public and has been withdrawn. published_at is kept.
 *
 * Its own namespace rather than App\States\Newsletter because that one is the SEND
 * pipeline: two state machines on one model must not share a namespace.
 *
 * Published is reachable from everywhere so an issue can always go back up, and
 * Hidden from anything that was public so it can always come down. Published ->
 * Draft is barred (taking a live issue down is an explicit Hidden, worth its own
 * audit row) and so is Draft -> Hidden (a draft was never public).
 */
abstract class NewsletterPublicationStatus extends State
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
