<?php

namespace App\States\Brand;

/**
 * Withdrawn from the public site. Unlike Draft, this brand HAS been announced —
 * published_at is kept so re-publishing remembers when it first went live.
 */
class Hidden extends BrandStatus
{
    public static string $name = 'hidden';
}
