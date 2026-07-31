<?php

namespace App\States\NewsletterPublication;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * The website archive alone — the email side is NewsletterSendStatus, and neither
 * ever reads the other. Article-shaped: published_at is the future date while
 * scheduled, and the real timestamp once live.
 *
 *  - Draft     : the default. Not listed anywhere.
 *  - Scheduled : waiting for published_at; newsletters:publish-scheduled flips it.
 *  - Published : live in the archive.
 *  - Hidden    : was public and has been withdrawn. published_at is kept.
 *
 * Published is reachable from everywhere so an issue can always go back up, and
 * Hidden from anything that was public so it can always come down. Published ->
 * Draft is barred (taking a live issue down is an explicit Hidden, worth its own
 * audit row) and so is Draft -> Hidden (a draft was never public).
 *
 * Its own directory, not shared with NewsletterSend: spatie resolves a state map
 * by scanning the base class's own folder, so two machines on one model must live
 * in two namespaces.
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
