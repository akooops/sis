<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\VisitTimeSlots\DeleteVisitTimeSlotRequest;
use App\Http\Requests\Admin\VisitTimeSlots\StoreVisitTimeSlotRequest;
use App\Http\Requests\Admin\VisitTimeSlots\UpdateVisitTimeSlotRequest;
use Illuminate\Http\Request;
use App\Models\VisitTimeSlot;
use App\Models\VisitService;
use Carbon\Carbon;

class VisitTimeSlotsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, VisitService $visitService)
    {
        $min = $this->indexService->checkIfEmpty($request->query('min'));
        $max = $this->indexService->checkIfEmpty($request->query('max'));
        
        $visitTimeSlots = $visitService->visitTimeSlots()->latest();
        
        if ($min && $max) {
            $startDate = Carbon::parse($min)->startOfDay();
            $endDate = Carbon::parse($max)->endOfDay();
            
            $visitTimeSlots->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('starts_at', [$startDate, $endDate])
                      ->orWhereBetween('ends_at', [$startDate, $endDate])
                      ->orWhere(function ($q) use ($startDate, $endDate) {
                          $q->where('starts_at', '<=', $startDate)
                            ->where('ends_at', '>=', $endDate);
                      });
            });
        }else{
            $perPage = $this->indexService->limitPerPage($request->query('perPage', 10));
            $page = $this->indexService->checkPageIfNull($request->query('page', 1));
            $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

            if ($search) {
                $visitTimeSlots->where(function($query) use ($search) {
                    $query->where('id', $search)
                          ->orWhere('capacity', 'like', '%' . $search . '%')
                          ->orWhere('starts_at', 'like', '%' . $search . '%')
                          ->orWhere('ends_at', 'like', '%' . $search . '%');
                });
            }

            $visitTimeSlots = $visitTimeSlots->paginate($perPage, ['*'], 'page', $page);

            if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
                return response()->json([
                    'visitTimeSlots' => $visitTimeSlots->items(),
                    'pagination' => $this->indexService->handlePagination($visitTimeSlots)
                ]);
            }
        }


        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'visitTimeSlots' => $visitTimeSlots->get()
            ]);
        }

        return inertia('VisitTimeSlots/Index', [
            'visitService' => $visitService
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreVisitTimeSlotRequest $request, VisitService $visitService)
    {
        $visitTimeSlot = VisitTimeSlot::create(array_merge(
            $request->validated(),
            [
                'visit_service_id' => $visitService->id
            ]
        ));
        
        return response()->json([
            'success' => true,
            'message' => 'Visit Time Slot created successfully'
        ]);
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(VisitTimeSlot $visitTimeSlot, UpdateVisitTimeSlotRequest $request)
    {
        $visitTimeSlot->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Visit Time Slot updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(VisitTimeSlot $visitTimeSlot, DeleteVisitTimeSlotRequest $request)
    {
        $visitTimeSlot->delete();

        return redirect()->route('admin.visit-time-slots.index', $visitTimeSlot->visit_service_id)
                        ->with('success','Visit Time Slot deleted successfully');
    }
}
