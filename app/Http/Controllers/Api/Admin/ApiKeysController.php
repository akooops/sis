<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\ApiKey\ApiKeyData;
use App\Data\ApiKey\StoreApiKeyData;
use App\Data\ApiKey\UpdateApiKeyData;
use App\Http\Controllers\Api\ApiController;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ApiKeysController extends ApiController
{
    public function index(): JsonResponse
    {
        $apiKeys = QueryBuilder::for(ApiKey::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                $this->search(['id', 'name', 'prefix']),
            ])
            ->allowedSorts(['id', 'name', 'last_used_at', 'expires_at', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(ApiKeyData::collect($apiKeys, PaginatedDataCollection::class), 'API keys retrieved successfully');
    }

    public function show(ApiKey $apiKey): JsonResponse
    {
        return $this->respond(ApiKeyData::from($apiKey), 'API key retrieved successfully');
    }

    public function store(StoreApiKeyData $data): JsonResponse
    {
        [$apiKey, $token] = ApiKey::issue($data->toArray());

        return $this->respond([
            'api_key' => ApiKeyData::from($apiKey),
            'token' => $token,
        ], 'API key created successfully', 201);
    }

    public function update(UpdateApiKeyData $data, ApiKey $apiKey): JsonResponse
    {
        $apiKey->update($data->toArray());

        return $this->respond(ApiKeyData::from($apiKey->fresh()), 'API key updated successfully');
    }

    public function destroy(ApiKey $apiKey): JsonResponse
    {
        $apiKey->delete();

        return $this->respond(null, 'API key deleted successfully');
    }

    /**
     * Issue a new secret, invalidating the previous token. Returns the new token once.
     */
    public function rotate(ApiKey $apiKey): JsonResponse
    {
        $token = $apiKey->rotate();

        return $this->respond([
            'api_key' => ApiKeyData::from($apiKey->fresh()),
            'token' => $token,
        ], 'API key rotated successfully');
    }

    public function revoke(ApiKey $apiKey): JsonResponse
    {
        $apiKey->revoke();

        return $this->respond(ApiKeyData::from($apiKey->fresh()), 'API key revoked successfully');
    }
}
