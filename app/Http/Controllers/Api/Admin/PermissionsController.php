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
 * Permissions are code-defined and seeded (database/seeders/PermissionsSeeder),
 * never authored through the API — hence read-only, index and show only.
 * supports_web/supports_api say which channel a code counts on: a session user
 * is checked against supports_web, an API key against supports_api, so the same
 * code can be granted to both and honoured for only one.
 */
class PermissionsController extends ApiController
{
    public function index(): JsonResponse
    {
        $permissions = QueryBuilder::for(Permission::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('supports_api'),
                AllowedFilter::exact('supports_web'),
                $this->search(['id', 'name', 'code']),
            ])
            ->allowedSorts(['id', 'name', 'code', 'created_at'])
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
