<?php

namespace App\States\Newsletter;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Where a broadcast sits in the send pipeline — one-way, not an editorial toggle.
 *
 *  - Draft     : the default. Being written, nothing queued.
 *  - Scheduled : waiting for scheduled_at; newsletters:send-scheduled picks it up.
 *                "Send now" is this, with scheduled_at = now.
 *  - Sending   : the command has claimed it and is dispatching a job per subscriber.
 *  - Sent      : TERMINAL. The mail is out of our hands, so a sent newsletter can
 *                never go back to any other state.
 *  - Failed    : nothing was dispatched (no group, or no active subscriber). Fix it
 *                and it can go back to Draft or Scheduled.
 *
 * Only Draft and Scheduled are settable through the API — Sending/Sent/Failed are
 * written by SendScheduledNewsletters and ShipNewsletter.
 */
abstract class NewsletterStatus extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, Scheduled::class)
            ->allowTransition(Scheduled::class, Draft::class)
            ->allowTransition(Scheduled::class, Sending::class)
            ->allowTransition(Sending::class, Sent::class)
            ->allowTransition(Sending::class, Failed::class)
            ->allowTransition(Failed::class, Draft::class)
            ->allowTransition(Failed::class, Scheduled::class);
    }
}
