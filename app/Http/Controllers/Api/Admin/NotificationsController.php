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
 * The signed-in admin's own notification inbox (the bell drawer + notifications
 * page). Auth-only, never permission-gated — every admin has an inbox, and
 * creation happens exclusively in observers via NotificationService::send().
 *
 * The API is keyed by the NOTIFICATION id (what the frontend shows); read and
 * delete resolve the current user's own notification_users row for it, so a
 * notification that never reached this user is simply a 404 and one user can
 * never touch another's read state or copy. Deleting removes only this user's
 * copy — the notification itself stays for its other recipients.
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
