<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Notification\NotificationData;
use App\Data\Notification\StoreNotificationData;
use App\Http\Controllers\Api\ApiController;
use App\Models\NotificationGroupUser;
use App\Models\Notification;
use App\Models\NotificationGroup;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Admin compose + sent-history. Sending fans out via NotificationService (which
 * resolves recipients from the groups that route the chosen type); the in-app
 * inbox itself is served by InboxController, not here.
 */
class NotificationsController extends ApiController
{
    public function index(): JsonResponse
    {
        $notifications = QueryBuilder::for(Notification::class)
            ->withCount('notificationUsers')
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('type'),
                $this->search(['id', 'title', 'type']),
            ])
            ->allowedSorts(['id', 'type', 'title', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(NotificationData::collect($notifications, PaginatedDataCollection::class), 'Notifications retrieved successfully');
    }

    /**
     * How many users a notification of the given type would reach, for the compose
     * form's live "will reach N users" preview. Mirrors the recipient resolution
     * in NotificationService::send.
     */
    public function preview(Request $request): JsonResponse
    {
        $type = (string) $request->query('type', '');

        $groupIds = NotificationGroup::whereHas('types', fn ($q) => $q->where('code', $type))->pluck('id');

        $count = $groupIds->isEmpty() ? 0 : NotificationGroupUser::whereIn('notification_group_id', $groupIds)
            ->distinct('user_id')
            ->count('user_id');

        return $this->respond(['recipients_count' => $count], 'Recipient preview retrieved successfully');
    }

    public function show(Notification $notification): JsonResponse
    {
        $notification->loadCount('notificationUsers');

        return $this->respond(NotificationData::from($notification), 'Notification retrieved successfully');
    }

    public function store(StoreNotificationData $data): JsonResponse
    {
        $notification = NotificationService::send($data->type, [
            'title' => $data->title,
            'body' => $data->body,
            'route_name' => $data->route_name,
            'route_params' => $data->route_params,
            'icon' => $data->icon,
        ]);

        $notification->loadCount('notificationUsers');

        return $this->respond(NotificationData::from($notification), 'Notification sent successfully', 201);
    }

    public function destroy(Notification $notification): JsonResponse
    {
        $notification->delete();

        return $this->respond(null, 'Notification deleted successfully');
    }
}
