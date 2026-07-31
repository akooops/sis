<?php

namespace App\States\NewsletterPublication;

/**
 * Withdrawn from the archive. Unlike Draft, this issue HAS been public —
 * published_at is kept so re-publishing remembers when it first went live.
 */
class Hidden extends NewsletterPublicationStatus
{
    public static string $name = 'hidden';
}
