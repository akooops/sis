<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\ApiKeyPermission\ApiKeyPermissionData;
use App\Data\ApiKeyPermission\StoreApiKeyPermissionData;
use App\Http\Controllers\Api\ApiController;
use App\Models\ApiKey;
use App\Models\ApiKeyPermission;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * The permissions granted to one API key, as pivot rows — paginated and searched
 * through the related permission, not the pivot's own columns. store() is
 * additive and idempotent: an already-granted code is a no-op, and the response
 * lists only the rows actually created, so an empty 201 means nothing was new.
 * A grant here is honoured only if the permission supports_api, so a web-only
 * code can be attached and still never let the key through.
 */
class ApiKeyPermissionsController extends ApiController
{
    public function index(ApiKey $apiKey): JsonResponse
    {
        $apiKeyPermissions = QueryBuilder::for($apiKey->apiKeyPermissions()->with('permission'))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('permission_id'),
                $this->searchRelationByColumns('permission', ['id', 'name', 'code']),
            ])
            ->allowedIncludes(['apiKey', 'permission'])
            ->allowedSorts(['id', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(ApiKeyPermissionData::collect($apiKeyPermissions, PaginatedDataCollection::class), 'API key permissions retrieved successfully');
    }

    public function store(StoreApiKeyPermissionData $data, ApiKey $apiKey): JsonResponse
    {
        $created = collect($data->permissions)
            ->map(fn (string $permissionId) => $apiKey->apiKeyPermissions()->firstOrCreate(['permission_id' => $permissionId]))
            ->filter->wasRecentlyCreated
            ->values();

        $created->each->load('permission');

        return $this->respond(ApiKeyPermissionData::collect($created->all()), 'Permissions assigned successfully', 201);
    }

    public function destroy(ApiKeyPermission $apiKeyPermission): JsonResponse
    {
        $apiKeyPermission->delete();

        return $this->respond(null, 'Permission removed successfully');
    }
}
