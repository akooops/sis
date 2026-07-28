<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\MenuItem\MenuItemData;
use App\Data\MenuItem\ReorderMenuItemsData;
use App\Data\MenuItem\StoreMenuItemData;
use App\Data\MenuItem\UpdateMenuItemData;
use App\Enums\MorphType;
use App\Http\Controllers\Api\ApiController;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MenuItemsController extends ApiController
{
    /** children.linkable: the tree is two levels, so both are eager loaded here. */
    public function index(): JsonResponse
    {
        $items = QueryBuilder::for(MenuItem::class)
            ->with(['menu', 'linkable', 'children.linkable'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('menu_id'),
                AllowedFilter::exact('parent_id'),
                // Takes the public alias and matches the stored class name.
                $this->morphType('linkable_type'),
                $this->searchTranslations(
                    ['id', 'name', 'url'],
                    ['title'],
                    Language::enabledCodes(),
                ),
            ])
            ->allowedSorts(['id', 'name', 'order', 'created_at'])
            // Display order by default: this is a menu, not a log.
            ->defaultSort('order')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(MenuItemData::collect($items, PaginatedDataCollection::class), 'Menu items retrieved successfully');
    }

    /** Nested list for one menu — the menu is the route, so it is not re-loaded. */
    public function forMenu(Menu $menu): JsonResponse
    {
        $items = QueryBuilder::for(MenuItem::query()->where('menu_id', $menu->getKey()))
            ->with(['linkable', 'children.linkable'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('parent_id'),
                $this->morphType('linkable_type'),
                $this->searchTranslations(
                    ['id', 'name', 'url'],
                    ['title'],
                    Language::enabledCodes(),
                ),
            ])
            ->allowedSorts(['id', 'name', 'order', 'created_at'])
            ->defaultSort('order')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(MenuItemData::collect($items, PaginatedDataCollection::class), 'Menu items retrieved successfully');
    }

    public function show(MenuItem $menuItem): JsonResponse
    {
        return $this->respond(MenuItemData::from($menuItem->load(['menu', 'linkable', 'children.linkable'])), 'Menu item retrieved successfully');
    }

    public function store(StoreMenuItemData $data): JsonResponse
    {
        $default = Language::defaultCode();

        $item = MenuItem::create([
            'menu_id' => $data->menu_id,
            'parent_id' => $data->parent_id,
            'name' => $data->name,
            'url' => $data->url,
            // The column stores the class; the API only ever speaks the alias.
            'linkable_type' => MorphType::classFor($data->linkable_type),
            'linkable_id' => $data->linkable_id,
            'title' => [$default => $data->title],
            'order' => MenuItem::nextOrder($data->menu_id, $data->parent_id),
        ]);

        return $this->respond(MenuItemData::from($item->fresh()->load(['menu', 'linkable', 'children.linkable'])), 'Menu item created successfully', 201);
    }

    public function update(UpdateMenuItemData $data, MenuItem $menuItem): JsonResponse
    {
        $attributes = [
            'menu_id' => $data->menu_id,
            'parent_id' => $data->parent_id,
            'name' => $data->name,
            // Not Optional, so nulls come through and actually clear the link.
            'url' => $data->url,
            'linkable_type' => MorphType::classFor($data->linkable_type),
            'linkable_id' => $data->linkable_id,
            'title' => $data->title,
        ];

        // Read before the update: save() resyncs the original attributes.
        $leftMenu = $data->menu_id !== $menuItem->menu_id;

        // Its old position means nothing in the group it landed in.
        if ($leftMenu || $data->parent_id !== $menuItem->parent_id) {
            $attributes['order'] = MenuItem::nextOrder($data->menu_id, $data->parent_id);
        }

        DB::transaction(function () use ($data, $menuItem, $attributes, $leftMenu) {
            // mergeTranslations: the form only carries the enabled locales, so a plain
            // assignment would drop every disabled one.
            $menuItem->update($menuItem->mergeTranslations($attributes));

            // Children follow their parent, or they would sit in a menu it left.
            if ($leftMenu) {
                $menuItem->children()->update(['menu_id' => $data->menu_id]);
            }
        });

        return $this->respond(MenuItemData::from($menuItem->fresh()->load(['menu', 'linkable', 'children.linkable'])), 'Menu item updated successfully');
    }

    /**
     * A parent owns its children, so they go with it.
     *
     * Iterated rather than a builder delete: a builder delete fires no events, so
     * neither their observer nor the audit rows for them would ever run.
     */
    public function destroy(MenuItem $menuItem): JsonResponse
    {
        $menuItem->delete();

        return $this->respond(null, 'Menu item deleted successfully');
    }

    /**
     * The whole tree, flat and in submitted order. Position within a sibling group
     * IS the order, and parent_id is rewritten from the same row — a drag can nest
     * and reposition at once.
     *
     * Builder updates: position is presentation, and a row per drag would drown the
     * audit log.
     */
    public function reorder(ReorderMenuItemsData $data): JsonResponse
    {
        // One counter per sibling group, keyed by parent ('' = the roots).
        $positions = [];

        foreach ($data->items as $item) {
            $parentId = $item['parent_id'] ?? null;
            $key = $parentId ?? '';
            $positions[$key] = ($positions[$key] ?? -1) + 1;

            MenuItem::query()->whereKey($item['id'])->update([
                'parent_id' => $parentId,
                'order' => $positions[$key],
            ]);
        }

        return $this->respond(null, 'Menu items reordered successfully');
    }
}
