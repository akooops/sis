<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Role\RoleData;
use App\Data\Role\StoreRoleData;
use App\Data\Role\UpdateRoleData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RolesController extends ApiController
{
    public function index(): JsonResponse
    {
        $roles = QueryBuilder::for(Role::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('is_default'),
                AllowedFilter::partial('name'),
                $this->search(['name']),
                $this->relatedId('permission', 'permissions'),
                AllowedFilter::trashed(),
            ])
            ->allowedSorts(['id', 'name', 'is_default', 'created_at'])
            ->defaultSort('-created_at')
            ->allowedIncludes(['permissions'])
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(RoleData::collect($roles, PaginatedDataCollection::class), 'Roles retrieved successfully');
    }

    public function show(Role $role): JsonResponse
    {
        return $this->respond(RoleData::from($role->load('permissions')), 'Role retrieved successfully');
    }

    public function store(StoreRoleData $data): JsonResponse
    {
        $role = Role::create($data->toArray());

        return $this->respond(RoleData::from($role), 'Role created successfully', 201);
    }

    public function update(UpdateRoleData $data, Role $role): JsonResponse
    {
        $role->update($data->toArray());

        return $this->respond(RoleData::from($role->fresh()), 'Role updated successfully');
    }

    public function destroy(Role $role): JsonResponse
    {
        $role->delete();

        return $this->respond(null, 'Role deleted successfully');
    }

    public function restore(string $role): JsonResponse
    {
        $role = Role::onlyTrashed()->findOrFail($role);
        $role->restore();

        return $this->respond(RoleData::from($role), 'Role restored successfully');
    }

    public function forceDestroy(string $role): JsonResponse
    {
        Role::onlyTrashed()->findOrFail($role)->forceDelete();

        return $this->respond(null, 'Role permanently deleted successfully');
    }
}
