<?php

namespace App\States\Page;

/**
 * Withdrawn from the public site. Unlike Draft, this page HAS been announced —
 * published_at is kept so re-publishing remembers when it first went live.
 */
class Hidden extends PageStatus
{
    public static string $name = 'hidden';
}
