<?php

namespace App\Http\Controllers\Admin;

use App\Models\Facility;
use App\Models\FacilityReservation;
use Illuminate\Http\Request;

class FacilityReservationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Facility $facility)
    {
        $perPage = $this->indexService->limitPerPage($request->query('perPage', 10));
        $page = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $facility_time_slot_id = $this->indexService->checkIfSearchEmpty($request->query('facility_time_slot_id'));

        $facilityReservations = $facility->facilityReservations()->with('facilityTimeSlot')->latest();

        if($facility_time_slot_id){
            $facilityReservations->where('facility_time_slot_id', $facility_time_slot_id);
        }

        if ($search) {
            $facilityReservations->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhere('phone', 'like', '%' . $search . '%')
                      ->orWhere('message', 'like', '%' . $search . '%');
            });
        }

        $facilityReservations = $facilityReservations->paginate($perPage, ['*'], 'page', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'facilityReservations' => $facilityReservations->items(),
                'pagination' => $this->indexService->handlePagination($facilityReservations)
            ]);
        }

        return inertia('FacilityReservations/Index', [
            'facility' => $facility,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(FacilityReservation $facilityReservation)
    {
        $facilityReservation->load(['facility', 'facilityTimeSlot']);

        return inertia('FacilityReservations/Show', [
            'facilityReservation' => $facilityReservation,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(FacilityReservation $facilityReservation)
    {
        $facilityReservation->delete();

        return redirect()->route('admin.facility-reservations.index', $facilityReservation->facility_id)
                        ->with('success','Facility Reservation deleted successfully');
    }
}
