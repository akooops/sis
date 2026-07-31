<?php

namespace App\States\NewsletterSend;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * The email broadcast alone — the website side is NewsletterPublicationStatus, and
 * neither ever reads the other. Same shape: sent_at is the future date while
 * scheduled, and the moment it went out once sent.
 *
 *  - Draft     : the default. Nothing queued.
 *  - Scheduled : waiting for sent_at; newsletters:send-scheduled ships it.
 *  - Sent      : the jobs went out.
 *  - Failed    : claimed, but there was nobody to mail.
 *
 * Sent is NOT terminal: Sent -> Scheduled re-sends the issue, which is why the UI
 * confirms before offering it. Failed only goes back to Draft or Scheduled — fix
 * the audience, then re-queue.
 *
 * Its own directory, not shared with NewsletterPublication: spatie resolves a
 * state map by scanning the base class's own folder, so two machines on one model
 * must live in two namespaces.
 */
abstract class NewsletterSendStatus extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, Scheduled::class)
            ->allowTransition(Scheduled::class, Draft::class)
            ->allowTransition(Scheduled::class, Sent::class)
            ->allowTransition(Scheduled::class, Failed::class)
            ->allowTransition(Sent::class, Scheduled::class)
            ->allowTransition(Failed::class, Draft::class)
            ->allowTransition(Failed::class, Scheduled::class);
    }
}
