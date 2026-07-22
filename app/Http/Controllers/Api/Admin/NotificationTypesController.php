<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Notification\NotificationTypeData;
use App\Http\Controllers\Api\ApiController;
use App\Models\NotificationType;
use Illuminate\Http\JsonResponse;

/**
 * Read-only list of the seeded notification types (the type pickers in compose
 * and in the group form). Grown by code, so there is no store/update/destroy.
 */
class NotificationTypesController extends ApiController
{
    public function index(): JsonResponse
    {
        $types = NotificationType::query()
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

        return $this->respond(NotificationTypeData::collect($types), 'Notification types retrieved successfully');
    }
}
