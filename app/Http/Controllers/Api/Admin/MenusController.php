<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Menu\MenuData;
use App\Data\Menu\StoreMenuData;
use App\Data\Menu\UpdateMenuData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MenusController extends ApiController
{
    public function index(): JsonResponse
    {
        $menus = QueryBuilder::for(Menu::class)
            ->withCount('items')
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('is_system'),
                $this->search(['id', 'name', 'code']),
            ])
            ->allowedSorts(['id', 'name', 'code', 'created_at'])
            // A handful of rows that never change: alphabetical beats newest-first.
            ->defaultSort('name')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(MenuData::collect($menus, PaginatedDataCollection::class), 'Menus retrieved successfully');
    }

    public function show(Menu $menu): JsonResponse
    {
        return $this->respond(MenuData::from($menu->loadCount('items')), 'Menu retrieved successfully');
    }

    public function store(StoreMenuData $data): JsonResponse
    {
        $menu = Menu::create([
            'name' => $data->name,
            'code' => $data->code,
        ]);

        return $this->respond(MenuData::from($menu->loadCount('items')), 'Menu created successfully', 201);
    }

    public function update(UpdateMenuData $data, Menu $menu): JsonResponse
    {
        // The public site resolves a seeded menu by its code: renaming is fine,
        // re-keying would leave the site rendering nothing.
        if ($menu->is_system && $data->code !== $menu->code) {
            throw ValidationException::withMessages([
                'code' => "The {$menu->name} menu ships with the app and its code cannot change.",
            ]);
        }

        $menu->update([
            'name' => $data->name,
            'code' => $data->code,
        ]);

        return $this->respond(MenuData::from($menu->fresh()->loadCount('items')), 'Menu updated successfully');
    }

    /**
     * A menu owns its items, so they go with it — children first, or deleting a
     * parent would null their parent_id on the way out.
     *
     * Iterated rather than a builder delete: a builder delete fires no events, so
     * neither their observer nor the audit rows for them would ever run.
     */
    public function destroy(Menu $menu): JsonResponse
    {
        if ($menu->is_system) {
            throw ValidationException::withMessages([
                'is_system' => "The {$menu->name} menu ships with the app and cannot be deleted.",
            ]);
        }

        $menu->delete();

        return $this->respond(null, 'Menu deleted successfully');
    }
}
