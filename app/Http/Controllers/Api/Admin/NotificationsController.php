<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Notification\NotificationData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * The signed-in admin's own inbox. Auth-only, never permission-gated; rows are
 * created exclusively by observers via NotificationService::send().
 *
 * Keyed by NOTIFICATION id: read and delete resolve this user's own pivot row,
 * so one user can never touch another's copy, and delete removes only theirs.
 */
class NotificationsController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $inbox = QueryBuilder::for($request->user()->notificationUsers()->with('notification.type'))
            ->allowedFilters([
                AllowedFilter::callback('read', function ($query, $value) {
                    filter_var($value, FILTER_VALIDATE_BOOLEAN)
                        ? $query->whereNotNull('read_at')
                        : $query->whereNull('read_at');
                }),
                $this->searchRelationByColumns('notification', ['id', 'title', 'body']),
            ])
            ->allowedSorts(['created_at', 'read_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(NotificationData::collect($inbox, PaginatedDataCollection::class), 'Notifications retrieved successfully');
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->respond(
            ['unread_count' => $request->user()->unreadNotificationsCount()],
            'Unread count retrieved successfully',
        );
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        $row = $request->user()->notificationUsers()
            ->where('notification_id', $notification->id)
            ->firstOrFail();

        $row->markRead();
        $row->load('notification.type');

        return $this->respond(NotificationData::from($row), 'Notification marked as read');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->notificationUsers()->whereNull('read_at')->update(['read_at' => now()]);

        return $this->respond(['unread_count' => 0], 'All notifications marked as read');
    }

    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        $request->user()->notificationUsers()
            ->where('notification_id', $notification->id)
            ->firstOrFail()
            ->delete();

        return $this->respond(null, 'Notification removed successfully');
    }
}
