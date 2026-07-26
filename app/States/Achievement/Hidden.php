<?php

namespace App\States\Achievement;

/**
 * Withdrawn from the public site. Unlike Draft this achievement HAS been announced —
 * published_at is kept so re-publishing remembers when it first went live.
 */
class Hidden extends AchievementStatus
{
    public static string $name = 'hidden';
}
