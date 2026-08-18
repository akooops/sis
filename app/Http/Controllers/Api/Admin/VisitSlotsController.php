<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\VisitSlot\BulkVisitSlotData;
use App\Data\VisitSlot\StoreVisitSlotData;
use App\Data\VisitSlot\UpdateVisitSlotData;
use App\Data\VisitSlot\VisitSlotData;
use App\Http\Controllers\Api\ApiController;
use App\Models\VisitSlot;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class VisitSlotsController extends ApiController
{
    /**
     * How many rows one bulk generate may write.
     *
     * The payload is already bounded (a year of dates, a 5-minute floor on the
     * step), but those bounds MULTIPLY: a year of every weekday at 5-minute steps
     * across a 12-hour window is six figures of rows from one click. This is the
     * backstop that turns that into a message.
     */
    protected const BULK_MAX_ROWS = 2000;

    /**
     * THE MONTH FEED — the whole reason this module has an endpoint the others do
     * not.
     *
     * Returns every slot overlapping a window as a flat array, UNPAGINATED, because
     * a calendar shows a month and a month is not a page: paging it would mean
     * February arriving in two halves with no way to say which. The window is the
     * pagination, and it is capped so this stays a bounded query.
     *
     * Reservations are eager-loaded so VisitSlot::reservedCount() reads the loaded
     * relation instead of firing a count per slot — thirty slots would otherwise be
     * thirty-one queries.
     */
    public function calendar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visit_service_id' => ['required', 'string', 'exists:visit_services,id'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after:from'],
        ]);

        $from = CarbonImmutable::parse($validated['from']);
        $to = CarbonImmutable::parse($validated['to']);
        $max = (int) config('visits.feed_max_days', 62);

        if ($from->diffInDays($to) > $max) {
            throw ValidationException::withMessages([
                'to' => "A calendar window may not span more than {$max} days.",
            ]);
        }

        $slots = VisitSlot::query()
            ->with('reservations')
            ->where('visit_service_id', $validated['visit_service_id'])
            ->inRange($from, $to)
            ->orderBy('starts_at')
            ->get();

        return $this->respond(VisitSlotData::collect($slots), 'Visit slots retrieved successfully');
    }

    /**
     * The paginated list, for anything that is not the calendar — an export, a
     * filtered audit, a drawer listing one service's next few times.
     */
    public function index(): JsonResponse
    {
        $slots = QueryBuilder::for(VisitSlot::class)
            ->with(['service', 'reservations'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('visit_service_id'),
                AllowedFilter::exact('is_open'),
                $this->date('from', '>=', 'startOfDay', 'starts_at'),
                $this->date('to', '<=', 'endOfDay', 'starts_at'),
            ])
            ->allowedSorts(['id', 'starts_at', 'ends_at', 'capacity', 'created_at'])
            ->defaultSort('starts_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(VisitSlotData::collect($slots, PaginatedDataCollection::class), 'Visit slots retrieved successfully');
    }

    public function store(StoreVisitSlotData $data): JsonResponse
    {
        try {
            $slot = VisitSlot::create([
                'visit_service_id' => $data->visit_service_id,
                'starts_at' => CarbonImmutable::parse($data->starts_at),
                'ends_at' => CarbonImmutable::parse($data->ends_at),
                'capacity' => $data->capacity,
                'is_open' => $data->is_open,
            ]);
        } catch (UniqueConstraintViolationException) {
            // The index is the guarantee; this is what makes it readable. See
            // StoreVisitSlotData for why it is not a Rule::unique.
            throw ValidationException::withMessages([
                'starts_at' => 'This visit already has a time slot for exactly that period.',
            ]);
        }

        return $this->respond(VisitSlotData::from($slot->fresh()), 'Time slot created successfully', 201);
    }

    public function update(UpdateVisitSlotData $data, VisitSlot $visitSlot): JsonResponse
    {
        /*
         * CAPACITY MAY NOT DROP BELOW WHAT IS ALREADY TAKEN. Otherwise the slot
         * reports a negative remainder, the public calendar calls it full, and the
         * families holding those seats have no idea anything changed. Cancel a
         * booking first, or close the slot.
         */
        $taken = $visitSlot->reservations()->active()->count();

        if ($data->capacity < $taken) {
            throw ValidationException::withMessages([
                'capacity' => "This time slot already has {$taken} reservation(s); its limit cannot be lower than that.",
            ]);
        }

        try {
            $visitSlot->update([
                'starts_at' => CarbonImmutable::parse($data->starts_at),
                'ends_at' => CarbonImmutable::parse($data->ends_at),
                'capacity' => $data->capacity,
                'is_open' => $data->is_open,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'starts_at' => 'This visit already has a time slot for exactly that period.',
            ]);
        }

        return $this->respond(VisitSlotData::from($visitSlot->fresh()), 'Time slot updated successfully');
    }

    /**
     * REFUSED WHILE ANYONE IS BOOKED ON IT.
     *
     * The reservations cascade, so deleting a booked slot would erase the bookings
     * with no record that they existed. Closing the slot stops new bookings and
     * leaves the families who already have one — which is what somebody reaching
     * for delete on a booked slot almost always meant.
     */
    public function destroy(VisitSlot $visitSlot): JsonResponse
    {
        $taken = $visitSlot->reservations()->active()->count();

        if ($taken > 0) {
            throw ValidationException::withMessages([
                'id' => "This time slot has {$taken} reservation(s) and cannot be deleted. Close it instead, or cancel them first.",
            ]);
        }

        $visitSlot->delete();

        return $this->respond(null, 'Time slot deleted successfully');
    }

    /**
     * Generate a term of slots from a weekday mask and a repeating daily window.
     *
     * insertOrIgnore AGAINST THE UNIQUE INDEX rather than a query per candidate
     * row: the old implementation checked for each slot in PHP before writing it,
     * which is both N queries and still racy between two admins generating the
     * same week. Here the database decides, and what it skipped is the difference
     * between what we offered and what it took.
     *
     * A run that creates nothing is a 422, not a cheerful zero — "generated 0 time
     * slots" reads as success and sends somebody looking for a calendar bug.
     */
    public function bulk(BulkVisitSlotData $data): JsonResponse
    {
        $rows = $this->generate($data);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'days_of_week' => 'That range and weekday selection produce no time slots. Check the dates, the days and the times.',
            ]);
        }

        if (count($rows) > self::BULK_MAX_ROWS) {
            throw ValidationException::withMessages([
                'end_date' => 'That would create '.count($rows).' time slots at once, over the limit of '.self::BULK_MAX_ROWS.'. Generate a shorter range.',
            ]);
        }

        $before = VisitSlot::query()->where('visit_service_id', $data->visit_service_id)->count();

        // Chunked because a single INSERT of two thousand rows can exceed
        // max_allowed_packet on a default MySQL.
        foreach (array_chunk($rows, 200) as $chunk) {
            VisitSlot::query()->insertOrIgnore($chunk);
        }

        $after = VisitSlot::query()->where('visit_service_id', $data->visit_service_id)->count();
        $created = $after - $before;
        $skipped = count($rows) - $created;

        if ($created === 0) {
            throw ValidationException::withMessages([
                'start_date' => 'Every one of those '.count($rows).' time slots already exists.',
            ]);
        }

        return $this->respond(
            ['created' => $created, 'skipped' => $skipped],
            "Generated {$created} time slot(s)".($skipped > 0 ? ", skipped {$skipped} that already existed" : ''),
            201,
        );
    }

    /**
     * Every row the payload describes, ready for insertOrIgnore.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function generate(BulkVisitSlotData $data): array
    {
        $days = array_flip($data->days_of_week);
        $step = $data->slot_minutes + $data->break_minutes;
        $now = now();
        $rows = [];

        /*
         * The MODEL'S OWN id generator, not Str::ulid() directly.
         *
         * insertOrIgnore bypasses Eloquent, so nothing would otherwise assign a
         * key — and HasUlids lowercases what Str::ulid() returns, so reaching for
         * the helper here would quietly make every bulk-generated slot the only
         * uppercase id in the database.
         */
        $slot = new VisitSlot;

        $period = CarbonPeriod::create(
            CarbonImmutable::parse($data->start_date)->startOfDay(),
            CarbonImmutable::parse($data->end_date)->startOfDay(),
        );

        foreach ($period as $day) {
            if (! isset($days[$day->dayOfWeek])) {
                continue;
            }

            $windowStart = CarbonImmutable::parse($day->format('Y-m-d').' '.$data->start_time);
            $windowEnd = CarbonImmutable::parse($day->format('Y-m-d').' '.$data->end_time);

            for ($cursor = $windowStart; ; $cursor = $cursor->addMinutes($step)) {
                $endsAt = $cursor->addMinutes($data->slot_minutes);

                // A tour that would run past the end of the window is not a
                // shorter tour, it is one that does not fit.
                if ($endsAt > $windowEnd) {
                    break;
                }

                $rows[] = [
                    'id' => $slot->newUniqueId(),
                    'visit_service_id' => $data->visit_service_id,
                    'starts_at' => $cursor->format('Y-m-d H:i:s'),
                    'ends_at' => $endsAt->format('Y-m-d H:i:s'),
                    'capacity' => $data->capacity,
                    'is_open' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // Guard against a step of zero looping forever. slot_minutes has a
                // floor of 5 so this cannot happen today, but a raw insert loop is
                // the wrong place to rely on a validation rule staying put.
                if ($step <= 0) {
                    break;
                }
            }
        }

        return $rows;
    }
}
