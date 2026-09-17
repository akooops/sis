<?php

namespace App\Services\Legacy\Importers;

use App\Models\Article;
use App\Services\Legacy\ContentImporter;

/**
 * News articles.
 *
 * The old app had no article categories at all — `achievement_categories` was
 * the only classification it ever grew — so `category_id` is left null and the
 * admin assigns categories afterwards. Guessing one from a slug would be
 * inventing editorial structure the old site never had.
 */
class ArticlesImporter extends ContentImporter
{
    public function module(): string
    {
        return 'articles';
    }

    public function describe(): string
    {
        return 'News articles';
    }

    protected function source(): string
    {
        return 'articles';
    }

    protected function target(): string
    {
        return Article::class;
    }

    protected function thumbnailCollection(): ?string
    {
        return Article::THUMBNAIL_COLLECTION;
    }
}
