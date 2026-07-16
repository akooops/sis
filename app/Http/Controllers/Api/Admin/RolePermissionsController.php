<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\RolePermission\RolePermissionData;
use App\Data\RolePermission\StoreRolePermissionData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RolePermissionsController extends ApiController
{
    public function index(Role $role): JsonResponse
    {
        $rolePermissions = QueryBuilder::for($role->rolePermissions()->with('permission'))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('permission_id'),
                $this->searchRelationByColumns('permission', ['id', 'name', 'code']),
            ])
            ->allowedIncludes(['role', 'permission'])
            ->allowedSorts(['created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(RolePermissionData::collect($rolePermissions, PaginatedDataCollection::class), 'Role permissions retrieved successfully');
    }

    public function store(StoreRolePermissionData $data, Role $role): JsonResponse
    {
        $created = collect($data->permissions)
            ->map(fn (string $permissionId) => $role->rolePermissions()->firstOrCreate(['permission_id' => $permissionId]))
            ->filter->wasRecentlyCreated
            ->values();

        $created->each->load('permission');

        return $this->respond(RolePermissionData::collect($created->all()), 'Permissions assigned successfully', 201);
    }

    public function destroy(RolePermission $rolePermission): JsonResponse
    {
        $rolePermission->delete();

        return $this->respond(null, 'Permission removed successfully');
    }
}
