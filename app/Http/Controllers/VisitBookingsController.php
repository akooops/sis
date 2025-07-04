<?php

namespace App\Http\Controllers;

use App\Http\Requests\VisitBookings\StoreVisitBookingRequest;
use App\Models\Album;
use App\Models\Article;
use App\Models\Banner;
use App\Models\Event;
use App\Models\Grade;
use App\Models\Page;
use App\Models\Program;
use App\Models\VisitBooking;
use App\Models\VisitService;
use App\Models\VisitTimeSlot;
use App\Services\IndexService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class VisitBookingsController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function visitBookings(StoreVisitBookingRequest $request, VisitService $visitService)
    {
        $timeSlot = VisitTimeSlot::findOrFail($request->visit_time_slot_id);

        $booking = VisitBooking::create(array_merge(
            $request->validated(),
            [
                'visit_service_id' => $visitService->id
            ]
        ));

        // Create notification
        $this->notificationService->createVisitBookingNotification($booking);

        return response()->json([
                'status' => 'success',
                'message' => 'Booking created successfully!',
        ], 200);
    }
}
