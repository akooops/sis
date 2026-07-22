<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Notification\NotificationGroupData;
use App\Data\Notification\StoreNotificationGroupData;
use App\Data\Notification\UpdateNotificationGroupData;
use App\Http\Controllers\Api\ApiController;
use App\Models\NotificationGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * CRUD for notification groups — the routing configs (types × members). Member
 * add/remove is handled here (syncUsers); each member's delivery integrations are
 * managed per-membership by GroupUsersController.
 */
class NotificationGroupsController extends ApiController
{
    public function index(): JsonResponse
    {
        $groups = QueryBuilder::for(NotificationGroup::class)
            ->with('types')
            ->withCount(['types', 'users'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
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
        $notificationGroup->load(['types', 'users'])->loadCount(['types', 'users']);

        return $this->respond(NotificationGroupData::from($notificationGroup), 'Notification group retrieved successfully');
    }

    public function store(StoreNotificationGroupData $data): JsonResponse
    {
        // One transaction so a failed type/member sync can't leave a half-built group.
        $group = DB::transaction(function () use ($data) {
            $group = NotificationGroup::create([
                'name' => $data->name,
                'code' => $data->code,
                'description' => $data->description,
            ]);

            $group->syncTypes($data->notification_type_ids);
            $group->syncUsers($data->user_ids);

            return $group;
        });

        $group->load(['types', 'users'])->loadCount(['types', 'users']);

        return $this->respond(NotificationGroupData::from($group), 'Notification group created successfully', 201);
    }

    public function update(UpdateNotificationGroupData $data, NotificationGroup $notificationGroup): JsonResponse
    {
        DB::transaction(function () use ($data, $notificationGroup) {
            $notificationGroup->update([
                'name' => $data->name,
                'code' => $data->code,
                'description' => $data->description,
            ]);

            $notificationGroup->syncTypes($data->notification_type_ids);
            $notificationGroup->syncUsers($data->user_ids);
        });

        $notificationGroup->load(['types', 'users'])->loadCount(['types', 'users']);

        return $this->respond(NotificationGroupData::from($notificationGroup), 'Notification group updated successfully');
    }

    public function destroy(NotificationGroup $notificationGroup): JsonResponse
    {
        $notificationGroup->delete();

        return $this->respond(null, 'Notification group deleted successfully');
    }
}
