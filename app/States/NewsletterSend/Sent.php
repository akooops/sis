<?php

namespace App\States\NewsletterSend;

/**
 * The jobs went out, and sent_at says when. Not terminal — Sent -> Scheduled
 * re-sends the whole issue, so the UI confirms before offering that.
 */
class Sent extends NewsletterSendStatus
{
    public static string $name = 'sent';
}
