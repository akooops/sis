<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\VisitTimeSlots\OrderVisitTimeSlotsRequest;
use App\Http\Requests\Admin\VisitTimeSlots\StoreVisitTimeSlotRequest;
use App\Http\Requests\Admin\VisitTimeSlots\UpdateVisitTimeSlotRequest;
use App\Http\Requests\Admin\VisitTimeSlots\UpdateVisitTimeSlotTranslationRequest;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Models\VisitTimeSlot;
use App\Models\Media;
use App\Models\Page;
use App\Models\VisitService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class VisitTimeSlotsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, VisitService $visitService)
    {
        $visitTimeSlots = $visitService->visitTimeSlots()->latest()->get();

        return view('admin.visit-time-slots.index', [
            'visitService' => $visitService,
            'visitTimeSlots' => $visitTimeSlots 
        ]);
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(VisitService $visitService)
    {
        return view('admin.visit-time-slots.create', compact('visitService'));
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
        
        return redirect()->route('admin.visit-time-slots.index', $visitService->id)
                        ->with('success','Visit Time Slot created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(VisitTimeSlot $visitTimeSlot)
    {    
        return view('admin.visit-time-slots.show', compact('visitTimeSlot'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(VisitTimeSlot $visitTimeSlot)
    {
        return view('admin.visit-time-slots.edit', compact('visitTimeSlot'));
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

        return redirect()->route('admin.visit-time-slots.index', $visitTimeSlot->visit_service_id)
                        ->with('success','Visit Time Slot updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(VisitTimeSlot $visitTimeSlot)
    {
        $visitTimeSlot->delete();

        return redirect()->route('admin.visit-time-slots.index', $visitTimeSlot->visit_service_id)
                        ->with('success','Visit Time Slot deleted successfully');
    }
}
