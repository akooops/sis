<?php

namespace App\Http\Controllers\Admin;

use App\Models\Notification;
use App\Services\FileService;
use App\Services\IndexService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;
    protected $indexService;
    protected $fileService;

    public function __construct(IndexService $indexService,  FileService $fileService, NotificationService $notificationService)
    {
        parent::__construct($indexService, $fileService);

        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of notifications
     */
    public function index(Request $request)
    {
        $perPage = $this->indexService->limitPerPage($request->query('perPage', 10));
        $page = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $notifications = Notification::latest();

        if ($search) {
            $notifications->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('title', 'like', '%' . $search . '%')
                      ->orWhere('message', 'like', '%' . $search . '%')
                      ->orWhere('type', 'like', '%' . $search . '%');
            });
        }

        $notifications = $notifications->paginate($perPage, ['*'], 'notification', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'notifications' => $notifications->items(),
                'pagination' => $this->indexService->handlePagination($notifications)
            ]);
        }

        return inertia('Notifications/Index');
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount()
    {
        $count = $this->notificationService->getUnreadCount();

        return response()->json([
            'status' => 'success',
            'count' => $count
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification)
    {
        $notification->markAsRead();

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $this->notificationService->markAllAsRead();

        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read'
        ]);
    }
} 