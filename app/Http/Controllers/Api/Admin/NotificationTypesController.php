<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Notification\NotificationTypeData;
use App\Http\Controllers\Api\ApiController;
use App\Models\NotificationType;
use Illuminate\Http\JsonResponse;

/**
 * Read-only: the catalogue is grown by code, so there is no write side.
 *
 * Every row is offered — the whole table is subscribable, and nothing is
 * withheld from the group form.
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
