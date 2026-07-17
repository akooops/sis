<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Integration\IntegrationDriverData;
use App\Data\Integration\IntegrationTypeData;
use App\Http\Controllers\Api\ApiController;
use App\Models\IntegrationType;
use Illuminate\Http\JsonResponse;

class IntegrationTypesController extends ApiController
{
    /** The type catalogue with per-type counts — drives the card grid. Not paginated. */
    public function index(): JsonResponse
    {
        $types = IntegrationType::query()
            ->with('integrations')
            ->orderBy('sort')
            ->get();

        return $this->respond(IntegrationTypeData::collect($types), 'Integration types retrieved successfully');
    }

    /** The drivers registered under a type (with their field schema) — the add-form picker. Not paginated. */
    public function drivers(IntegrationType $integrationType): JsonResponse
    {
        $drivers = $integrationType->drivers()->orderBy('name')->get();

        return $this->respond(IntegrationDriverData::collect($drivers), 'Drivers retrieved successfully');
    }
}
