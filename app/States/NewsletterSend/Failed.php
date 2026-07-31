<?php

namespace App\States\NewsletterSend;

/**
 * One case only: the command claimed a sendable issue and found no active
 * recipient in its groups. Nothing was mailed, so marking it Sent would be a lie —
 * fix the audience and put it back to Draft or Scheduled.
 */
class Failed extends NewsletterSendStatus
{
    public static string $name = 'failed';
}
