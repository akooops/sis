<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\VisitTimeSlots\BulkStoreVisitTimeSlotRequest;
use App\Http\Requests\Admin\VisitTimeSlots\DeleteVisitTimeSlotRequest;
use App\Http\Requests\Admin\VisitTimeSlots\StoreVisitTimeSlotRequest;
use App\Http\Requests\Admin\VisitTimeSlots\UpdateVisitTimeSlotRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\VisitTimeSlot;
use App\Models\VisitService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

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
     * Bulk store time slots for each selected day of week within a date range.
     *
     * @param  BulkStoreVisitTimeSlotRequest  $request
     * @param  VisitService  $visitService
     * @return \Illuminate\Http\Response
     */
    public function bulkStore(BulkStoreVisitTimeSlotRequest $request, VisitService $visitService)
    {
        $data = $request->validated();

        $startDate  = Carbon::parse($data['start_date']);
        $endDate    = Carbon::parse($data['end_date']);
        $daysOfWeek = array_map('intval', $data['days_of_week']);
        $startTime  = $data['start_time'];
        $endTime    = $data['end_time'];
        $capacity   = (int) $data['capacity'];

        $period = CarbonPeriod::create($startDate, $endDate);

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($period, $daysOfWeek, $startTime, $endTime, $capacity, $visitService, &$created, &$skipped) {
            foreach ($period as $day) {
                if (!in_array($day->dayOfWeek, $daysOfWeek, true)) {
                    continue;
                }

                $startsAt = $day->copy()->setTimeFromTimeString($startTime);
                $endsAt   = $day->copy()->setTimeFromTimeString($endTime);

                $exists = VisitTimeSlot::where('visit_service_id', $visitService->id)
                    ->where('starts_at', $startsAt)
                    ->where('ends_at', $endsAt)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                VisitTimeSlot::create([
                    'visit_service_id' => $visitService->id,
                    'starts_at'        => $startsAt,
                    'ends_at'          => $endsAt,
                    'capacity'         => $capacity,
                ]);

                $created++;
            }
        });

        if ($created === 0) {
            return response()->json([
                'success' => false,
                'message' => $skipped > 0
                    ? "No new time slots were created. {$skipped} matching slot(s) already exist."
                    : 'No matching days were found in the selected range.',
            ], 422);
        }

        $message = "{$created} time slot(s) created successfully.";
        if ($skipped > 0) {
            $message .= " {$skipped} slot(s) were skipped because they already exist.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'created' => $created,
            'skipped' => $skipped,
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
