<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Permission\PermissionData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Permissions are seeded/code-defined, so this is read-only (no store/update/destroy).
 */
class PermissionsController extends ApiController
{
    public function index(): JsonResponse
    {
        $permissions = QueryBuilder::for(Permission::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::partial('code'),
                AllowedFilter::partial('name'),
                AllowedFilter::exact('supports_api'),
                AllowedFilter::exact('supports_web'),
                $this->search(['code', 'name']),
                AllowedFilter::trashed(),
            ])
            ->allowedSorts(['id', 'code', 'name', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(PermissionData::collect($permissions, PaginatedDataCollection::class), 'Permissions retrieved successfully');
    }

    public function show(Permission $permission): JsonResponse
    {
        return $this->respond(PermissionData::from($permission), 'Permission retrieved successfully');
    }
}
