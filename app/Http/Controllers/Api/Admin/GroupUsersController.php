<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Notification\GroupUserData;
use App\Data\Notification\SyncGroupUserIntegrationsData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Integration;
use App\Models\NotificationGroupUser;
use App\Models\NotificationGroup;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * A group's memberships and each member's delivery integrations. Membership
 * add/remove lives on the group form (syncUsers); this manages the per-member
 * integration set, which is what actually routes a user's external delivery.
 */
class GroupUsersController extends ApiController
{
    public function index(NotificationGroup $notificationGroup): JsonResponse
    {
        $members = QueryBuilder::for($notificationGroup->groupUsers()->with(['user', 'integrations']))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                $this->searchRelationByColumns('user', ['id', 'firstname', 'lastname', 'username', 'email']),
            ])
            ->allowedSorts(['id', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(GroupUserData::collect($members, PaginatedDataCollection::class), 'Group members retrieved successfully');
    }

    public function updateIntegrations(SyncGroupUserIntegrationsData $data, NotificationGroupUser $groupUser): JsonResponse
    {
        // Only email/sms integrations may deliver notifications — never ai.
        $ids = Integration::whereIn('id', $data->integration_ids)
            ->whereHas('type', fn ($q) => $q->whereIn('code', ['email', 'sms']))
            ->pluck('id')
            ->all();

        $groupUser->syncIntegrations($ids);

        $groupUser->load(['user', 'integrations']);

        return $this->respond(GroupUserData::from($groupUser), 'Member integrations updated successfully');
    }
}
