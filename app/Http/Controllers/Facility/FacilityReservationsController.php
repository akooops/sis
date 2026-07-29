<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Http\Requests\Facility\StoreFacilityReservationRequest;
use App\Models\FacilityReservation;
use App\Services\NotificationService;

class FacilityReservationsController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function store(StoreFacilityReservationRequest $request)
    {
        $facility = $request->attributes->get('facility');

        $reservation = FacilityReservation::create(array_merge(
            $request->validated(),
            [
                'facility_id' => $facility->id,
            ]
        ));

        $this->notificationService->createFacilityReservationNotification($reservation);

        return response()->json([
            'status' => 'success',
            'message' => 'Reservation request sent successfully!',
        ], 200);
    }
}
