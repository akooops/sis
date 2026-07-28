<?php

namespace App\States\Banner;

/**
 * Withdrawn from the public site. Unlike Draft, this banner HAS been shown —
 * published_at is kept so re-publishing remembers when it first went live.
 */
class Hidden extends BannerStatus
{
    public static string $name = 'hidden';
}
