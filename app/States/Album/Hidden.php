<?php

namespace App\States\Album;

/**
 * Withdrawn from the public site. Unlike Draft this album HAS been announced —
 * published_at is kept so re-publishing remembers when it first went live.
 */
class Hidden extends AlbumStatus
{
    public static string $name = 'hidden';
}
