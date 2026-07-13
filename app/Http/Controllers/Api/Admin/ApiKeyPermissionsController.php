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

class ApiKeyPermissionsController extends ApiController
{
    public function index(ApiKey $apiKey): JsonResponse
    {
        $apiKeyPermissions = QueryBuilder::for($apiKey->apiKeyPermissions()->with('permission'))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('permission_id'),
                $this->searchRelation('permission', ['code', 'name']),
            ])
            ->allowedIncludes(['apiKey', 'permission'])
            ->allowedSorts(['created_at'])
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
