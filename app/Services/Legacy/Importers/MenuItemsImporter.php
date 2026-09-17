<?php

namespace App\Services\Legacy\Importers;

use App\Models\MenuItem;
use App\Services\Legacy\LegacyImporter;

/**
 * Menu items — RUNS LAST, and that is the point of it being its own module.
 *
 * An item can link to a page, an article, a programme, a posting, a facility:
 * everything. So it cannot be imported until everything else has been, or half
 * the navigation would come across as unlinked labels. Menus themselves run
 * EARLY, because a page carries a `menu_id`, and splitting the two is what breaks
 * that cycle without anyone having to reason about it.
 *
 * TWO PASSES over the same rows. The first writes every item with no parent; the
 * second sets `parent_id`. Legacy ids happen to run parent-before-child most of
 * the time, and "most of the time" is exactly the kind of assumption that makes a
 * migration drop a submenu on one install and not another.
 *
 * An unresolvable link becomes a plain label rather than a broken one. A menu is
 * chrome; SiteContext already returns null from its resolver rather than throwing,
 * for the same reason.
 */
class MenuItemsImporter extends LegacyImporter
{
    protected int $unlinked = 0;

    public function module(): string
    {
        return 'menu-items';
    }

    public function describe(): string
    {
        return 'Menu items, their links and their nesting';
    }

    public function dependsOn(): array
    {
        return ['menus'];
    }

    public function sources(): array
    {
        return ['menu_items'];
    }

    public function run(): void
    {
        $translations = $this->translationsFor('App\Models\MenuItem');

        $this->each('menu_items', function (object $row) use ($translations) {
            $menuId = $this->c->map->find('menus', $row->menu_id);

            if (! $menuId) {
                $this->c->warn("Menu item [{$row->name}] belongs to a menu that was not imported — skipped.");
                $this->c->skipped();

                return;
            }

            $model = $this->model(MenuItem::class, 'menu_items', (int) $row->id);

            $model->name = (string) $row->name;
            $model->menu_id = $menuId;
            $model->order = (int) ($row->order ?? 0);
            $model->url = $row->url ?: null;

            $this->link($row, $model);

            foreach ($this->c->translations->pick($translations, (int) $row->id, ['title']) as $column => $values) {
                $model->setTranslations($column, array_merge($model->getTranslations($column), $values));
            }

            $model->created_at = $row->created_at ?? $model->created_at;

            $this->save($model, 'menu_items', (int) $row->id);
        });

        $this->nest();

        if ($this->unlinked > 0) {
            $this->c->note(
                "{$this->unlinked} menu item(s) pointed at something this app does not have, or at content that was "
                .'not imported, and are now plain labels. Re-point them in the admin, or re-run with the missing module.'
            );
        }
    }

    /**
     * Resolve the legacy morph to this app's.
     *
     * TWO TRANSLATIONS, not one: the class name may have been renamed
     * (`JobPosting` → `JobOffer`), and the id certainly has been (integer →
     * ULID). Both go through config and the import map respectively, so neither
     * is guessed at here.
     */
    protected function link(object $row, MenuItem $model): void
    {
        $legacyType = $row->linkable_type ?? null;
        $legacyId = $row->linkable_id ?? null;

        if (! $legacyType || ! $legacyId) {
            return;
        }

        $class = $this->morph($legacyType);

        // The table is resolved from the LEGACY class, not from $class — two
        // legacy tables share one destination here. See LEGACY_SOURCES.
        $source = $this->sourceFor($legacyType);
        $id = $class && $source ? $this->c->map->find($source, $legacyId) : null;

        if (! $id) {
            $this->unlinked++;
            $model->linkable_type = null;
            $model->linkable_id = null;

            return;
        }

        $model->linkable_type = $class;
        $model->linkable_id = $id;
    }

    /**
     * Second pass: `menu_item_id` → `parent_id`.
     *
     * Separate from the first because a child may legitimately have a lower id
     * than its parent, and a one-pass import would silently flatten it.
     */
    protected function nest(): void
    {
        $this->each('menu_items', function (object $row) {
            if (empty($row->menu_item_id)) {
                return;
            }

            $id = $this->c->map->find('menu_items', $row->id);
            $parentId = $this->c->map->find('menu_items', $row->menu_item_id);

            if (! $id || ! $parentId || $this->c->dryRun) {
                return;
            }

            $model = MenuItem::find($id);

            if ($model && $model->parent_id !== $parentId) {
                $model->parent_id = $parentId;
                $model->save();
            }
        });
    }
}
