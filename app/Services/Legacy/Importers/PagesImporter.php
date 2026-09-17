<?php

namespace App\Services\Legacy\Importers;

use App\Models\Page;
use App\Services\Legacy\ContentImporter;
use Illuminate\Database\Eloquent\Model;

/**
 * Pages, matched to the seeded system pages by slug.
 *
 * THE SLUG IS THE WHOLE STORY HERE. Fifteen pages in this app are `is_system`
 * because a controller resolves them by slug — rename `contact` and the contact
 * page 404s. The old app had the same idea under a different column name, and
 * used the same slugs, so a legacy `contact` page must UPDATE the seeded one and
 * bring its translated title and body with it, not insert a second row that
 * fails the unique index.
 *
 * ContentImporter's natural-key fallback does exactly that, and `is_system` is
 * OR-ed rather than assigned: a page this app considers system-critical stays
 * so even if the old install had unflagged it.
 */
class PagesImporter extends ContentImporter
{
    public function module(): string
    {
        return 'pages';
    }

    public function describe(): string
    {
        return 'Pages (system pages matched by slug)';
    }

    public function dependsOn(): array
    {
        return ['files', 'menus'];
    }

    protected function source(): string
    {
        return 'pages';
    }

    protected function target(): string
    {
        return Page::class;
    }

    protected function thumbnailCollection(): ?string
    {
        return Page::THUMBNAIL_COLLECTION;
    }

    protected function extra(object $row, Model $model): void
    {
        // Never demote a page this app resolves by slug.
        $model->is_system = (bool) ($row->is_system_page ?? false) || (bool) $model->is_system;

        // The "sub-menu shown on this page" link. Null when the menu module was
        // excluded from the run, which is a missing garnish and not a broken page.
        if (property_exists($row, 'menu_id')) {
            $model->menu_id = $this->c->map->find('menus', $row->menu_id) ?? $model->menu_id;
        }
    }
}
