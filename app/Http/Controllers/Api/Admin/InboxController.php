<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Notification\NotificationInboxData;
use App\Http\Controllers\Api\ApiController;
use App\Models\NotificationUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * The signed-in user's own notification inbox (the bell drawer + notifications
 * page). Auth-only, never permission-gated — every admin has an inbox. Read state
 * is per user: a row is only ever this user's, and marking read touches only it.
 */
class InboxController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $inbox = QueryBuilder::for($request->user()->notificationUsers()->with('notification'))
            ->allowedFilters([
                AllowedFilter::callback('read', function ($query, $value) {
                    filter_var($value, FILTER_VALIDATE_BOOLEAN)
                        ? $query->whereNotNull('read_at')
                        : $query->whereNull('read_at');
                }),
                $this->searchRelationByColumns('notification', ['title', 'body', 'type']),
            ])
            ->allowedSorts(['created_at', 'read_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(NotificationInboxData::collect($inbox, PaginatedDataCollection::class), 'Inbox retrieved successfully');
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->respond(
            ['unread_count' => $request->user()->unreadNotificationsCount()],
            'Unread count retrieved successfully',
        );
    }

    public function markRead(Request $request, NotificationUser $notificationUser): JsonResponse
    {
        abort_unless($notificationUser->user_id === $request->user()->id, 403);

        $notificationUser->markRead();
        $notificationUser->load('notification');

        return $this->respond(NotificationInboxData::from($notificationUser), 'Notification marked as read');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->notificationUsers()->whereNull('read_at')->update(['read_at' => now()]);

        return $this->respond(['unread_count' => 0], 'All notifications marked as read');
    }
}
