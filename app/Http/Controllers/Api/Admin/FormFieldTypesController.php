<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Services\Forms\FieldTypeRegistry;
use Illuminate\Http\JsonResponse;

/**
 * The element catalogue the builder's palette and inspector render from.
 *
 * Read-only and not paginated: it is a config-backed registry, not a table, so
 * there is nothing to filter or sort and the whole list is a handful of rows.
 * Each entry carries its declared settings/validation schema, which the
 * inspector feeds straight into SchemaField — the same component the
 * integrations form uses for driver schemas.
 */
class FormFieldTypesController extends ApiController
{
    public function __construct(protected FieldTypeRegistry $registry) {}

    public function index(): JsonResponse
    {
        return $this->respond($this->registry->palette(), 'Form field types retrieved successfully');
    }
}
