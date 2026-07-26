<?php

namespace App\States\Article;

/**
 * Withdrawn from the public site. Unlike Draft this article HAS been announced —
 * published_at is kept so re-publishing remembers when it first went live.
 */
class Hidden extends ArticleStatus
{
    public static string $name = 'hidden';
}
