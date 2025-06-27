<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\VisitBookings\UpdateVisitBookingRequest;
use App\Http\Requests\Admin\VisitServices\OrderVisitServicesRequest;
use App\Http\Requests\Admin\VisitServices\StoreVisitServiceRequest;
use App\Http\Requests\Admin\VisitServices\UpdateVisitServiceRequest;
use App\Http\Requests\Admin\VisitServices\UpdateVisitServiceTranslationRequest;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Models\VisitService;
use App\Models\Media;
use App\Models\Page;
use App\Models\VisitBooking;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class VisitBookingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, VisitService $visitService)
    {
        $perPage = $this->indexService->limitPerPage($request->query('perPage', 10));
        $page = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $visitBookings = $visitService->visitBookings()->latest();

        if ($search) {
            $visitBookings->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('guardian_name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhere('phone', 'like', '%' . $search . '%')
                      ->orWhere('student_name', 'like', '%' . $search . '%')
                      ->orWhere('student_grade', 'like', '%' . $search . '%')
                      ->orWhere('student_school', 'like', '%' . $search . '%');
            });
        }

        $visitBookings = $visitBookings->paginate($perPage, ['*'], 'visitService', $page);

        return view('admin.visit-bookings.index', [
            'visitService' => $visitService,
            'visitBookings' => $visitBookings,
            'pagination' => $this->indexService->handlePagination($visitBookings)
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(VisitBooking $visitBooking)
    {    
        return view('admin.visit-bookings.show', compact('visitBooking'));
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(VisitBooking $visitBooking)
    {
        $visitBooking->delete();

        return redirect()->route('admin.visit-bookings.index', $visitBooking->visitService->id)
                        ->with('success','Visit Booking deleted successfully');
    }
}
