<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Notification\NotificationGroupData;
use App\Data\Notification\StoreNotificationGroupData;
use App\Data\Notification\UpdateNotificationGroupData;
use App\Http\Controllers\Api\ApiController;
use App\Models\NotificationGroup;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * CRUD for notification groups — the routing configs (types × members × one
 * optional email integration). Members are managed as their own pivot resource
 * (NotificationGroupUsersController), never through this form.
 */
class NotificationGroupsController extends ApiController
{
    public function index(): JsonResponse
    {
        $groups = QueryBuilder::for(NotificationGroup::class)
            ->with(['types', 'integration'])
            ->withCount(['types', 'users'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('integration_id'),
                $this->search(['id', 'name', 'code']),
            ])
            ->allowedSorts(['id', 'name', 'code', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(NotificationGroupData::collect($groups, PaginatedDataCollection::class), 'Notification groups retrieved successfully');
    }

    public function show(NotificationGroup $notificationGroup): JsonResponse
    {
        $notificationGroup->load(['types', 'integration'])->loadCount(['types', 'users']);

        return $this->respond(NotificationGroupData::from($notificationGroup), 'Notification group retrieved successfully');
    }

    public function store(StoreNotificationGroupData $data): JsonResponse
    {
        $group = NotificationGroup::create([
            'name' => $data->name,
            'code' => $data->code,
            'integration_id' => $data->integration_id,
        ]);

        $group->syncTypes($data->notification_type_ids);

        $group->load(['types', 'integration'])->loadCount(['types', 'users']);

        return $this->respond(NotificationGroupData::from($group), 'Notification group created successfully', 201);
    }

    public function update(UpdateNotificationGroupData $data, NotificationGroup $notificationGroup): JsonResponse
    {
        $notificationGroup->update([
            'name' => $data->name,
            'code' => $data->code,
            'integration_id' => $data->integration_id,
        ]);

        $notificationGroup->syncTypes($data->notification_type_ids);

        $notificationGroup->load(['types', 'integration'])->loadCount(['types', 'users']);

        return $this->respond(NotificationGroupData::from($notificationGroup), 'Notification group updated successfully');
    }

    public function destroy(NotificationGroup $notificationGroup): JsonResponse
    {
        $notificationGroup->delete();

        return $this->respond(null, 'Notification group deleted successfully');
    }
}
