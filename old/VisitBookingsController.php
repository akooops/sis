<?php

namespace App\Http\Controllers;

use App\Http\Requests\VisitBookings\StoreVisitBookingRequest;
use App\Models\VisitBooking;
use App\Models\VisitService;
use App\Models\VisitTimeSlot;
use App\Services\ERegistrationService;
use App\Services\NotificationService;

class VisitBookingsController extends Controller
{
    protected $notificationService;

    protected $eRegistrationService;

    public function __construct(
        NotificationService $notificationService,
        ERegistrationService $eRegistrationService
    ) {
        $this->notificationService = $notificationService;
        $this->eRegistrationService = $eRegistrationService;
    }

    public function visitBookings(StoreVisitBookingRequest $request, VisitService $visitService)
    {
        $timeSlot = VisitTimeSlot::findOrFail($request->visit_time_slot_id);

        $booking = VisitBooking::create(array_merge(
            $request->validated(),
            [
                'visit_service_id' => $visitService->id,
            ]
        ));

        $this->notificationService->createVisitBookingNotification($booking);

        $this->eRegistrationService->sendVisitBooking($booking);

        return response()->json([
            'status' => 'success',
            'message' => 'Booking created successfully!',
        ], 200);
    }
}
