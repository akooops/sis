<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Notification\NotificationGroupUserData;
use App\Data\Notification\StoreNotificationGroupUserData;
use App\Http\Controllers\Api\ApiController;
use App\Models\NotificationGroup;
use App\Models\NotificationGroupUser;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Group memberships as a pivot resource (mirrors user-roles). firstOrCreate and
 * model deletes fire the observer, so attach/detach are audited against the group.
 */
class NotificationGroupUsersController extends ApiController
{
    public function index(NotificationGroup $notificationGroup): JsonResponse
    {
        $members = QueryBuilder::for($notificationGroup->groupUsers()->with('user'))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('user_id'),
                $this->searchRelationByColumns('user', ['id', 'firstname', 'lastname', 'username', 'email']),
            ])
            ->allowedIncludes(['user', 'group'])
            ->allowedSorts(['id', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(NotificationGroupUserData::collect($members, PaginatedDataCollection::class), 'Group members retrieved successfully');
    }

    public function store(StoreNotificationGroupUserData $data, NotificationGroup $notificationGroup): JsonResponse
    {
        $created = collect($data->users)
            ->map(fn (string $userId) => $notificationGroup->groupUsers()->firstOrCreate(['user_id' => $userId]))
            ->filter->wasRecentlyCreated
            ->values();

        $created->each->load('user');

        return $this->respond(NotificationGroupUserData::collect($created->all()), 'Members added successfully', 201);
    }

    public function destroy(NotificationGroupUser $notificationGroupUser): JsonResponse
    {
        $notificationGroupUser->delete();

        return $this->respond(null, 'Member removed successfully');
    }
}
