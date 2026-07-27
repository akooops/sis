<?php

namespace App\States\Event;

/**
 * Withdrawn from the public site. Unlike Draft, this event HAS been announced —
 * published_at is kept so re-publishing remembers when it first went live.
 */
class Hidden extends EventStatus
{
    public static string $name = 'hidden';
}
