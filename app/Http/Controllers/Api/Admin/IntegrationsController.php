<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Integration\IntegrationData;
use App\Data\Integration\StoreIntegrationData;
use App\Data\Integration\UpdateIntegrationData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Integration;
use App\Services\Integrations\Registry;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IntegrationsController extends ApiController
{
    public function __construct(protected Registry $registry) {}

    public function index(): JsonResponse
    {
        $integrations = QueryBuilder::for(Integration::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('integration_type_id'),
                AllowedFilter::exact('is_enabled'),
                /*
                 * By type CODE rather than id, so a picker can ask for "the
                 * enabled captcha integrations" without first resolving the
                 * type row. An unknown code matches nothing, which is the right
                 * answer — a slot for a type nobody has configured is empty.
                 */
                AllowedFilter::callback('type', fn ($query, $value) => $query->ofType((string) $value)),
                $this->search(['id', 'name']),
            ])
            ->allowedSorts(['id', 'name', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(IntegrationData::collect($integrations, PaginatedDataCollection::class), 'Integrations retrieved successfully');
    }

    public function show(Integration $integration): JsonResponse
    {
        return $this->respond(IntegrationData::from($integration), 'Integration retrieved successfully');
    }

    public function store(StoreIntegrationData $data): JsonResponse
    {
        $driver = $this->registry->driver($data->driver);
        $config = $this->registry->applyValues($driver->schema(), [], $data->settings, forUpdate: false);

        $integration = Integration::create([
            'integration_type_id' => $data->integration_type_id,
            'driver' => $data->driver,
            'name' => $data->name,
            'config' => $config,
        ]);

        return $this->respond(IntegrationData::from($integration->fresh()), 'Integration created successfully', 201);
    }

    public function update(UpdateIntegrationData $data, Integration $integration): JsonResponse
    {
        $driver = $integration->resolveDriver();
        $settings = $data->settings instanceof Optional ? [] : $data->settings;
        $config = $this->registry->applyValues($driver->schema(), $integration->config ?? [], $settings, forUpdate: true);

        $integration->update([
            'name' => $data->name instanceof Optional ? $integration->name : $data->name,
            'config' => $config,
        ]);

        return $this->respond(IntegrationData::from($integration->fresh()), 'Integration updated successfully');
    }

    public function destroy(Integration $integration): JsonResponse
    {
        $integration->delete();

        return $this->respond(null, 'Integration deleted successfully');
    }

    public function toggle(Integration $integration): JsonResponse
    {
        $integration->update(['is_enabled' => ! $integration->is_enabled]);

        return $this->respond(IntegrationData::from($integration->fresh()), 'Integration updated successfully');
    }
}
