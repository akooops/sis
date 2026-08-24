<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\FacilityReservation\UpdateFacilityReservationData;
use App\Data\FacilityReservation\FacilityReservationData;
use App\Http\Controllers\Api\ApiController;
use App\Models\FacilityReservation;
use App\Services\Facilities\CsvReservationWriter;
use App\States\FacilityReservation\Attended;
use App\States\FacilityReservation\Cancelled;
use App\States\FacilityReservation\Confirmed;
use App\States\FacilityReservation\Contacted;
use App\States\FacilityReservation\NoShow;
use App\States\FacilityReservation\Pending;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The admissions desk queue: read-only, plus the six transitions and a note.
 *
 * THERE IS NO store(). A reservation is the record of something a family did —
 * SubmitController and ReservationProjector are the only writers. update() takes
 * the desk's internal note and nothing else, because everything that matters about
 * a booking moves through a transition with its own permission and its own audit
 * row. That is also why the permissions are PER TRANSITION rather than one
 * `update`: a school wants a receptionist who can confirm without being able to
 * cancel, and someone marking the register on the day who does neither.
 *
 * A disallowed transition is a 422 carrying the reason, never a 500: the state
 * machine's refusal is information the user can act on.
 */
class FacilityReservationsController extends ApiController
{
    public function index(): JsonResponse
    {
        $reservations = QueryBuilder::for(FacilityReservation::class)
            ->with(['visitor', 'facility', 'slot'])
            ->allowedFilters($this->filters())
            ->allowedSorts($this->sorts())
            // Nullable, and MySQL sorts NULL last on DESC, so a row the projector
            // never stamped falls to the bottom rather than heading the queue.
            ->defaultSort('-booked_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(FacilityReservationData::collect($reservations, PaginatedDataCollection::class), 'Bookings retrieved successfully');
    }

    public function show(FacilityReservation $facilityReservation): JsonResponse
    {
        $facilityReservation->load(['visitor', 'facility', 'slot', 'submission']);

        return $this->respond(FacilityReservationData::from($facilityReservation), 'Booking retrieved successfully');
    }

    /**
     * The CSV, under the same filters the list is showing.
     *
     * getEloquentBuilder() is the ONLY way to hand the filtered query on: a
     * QueryBuilder is not an Eloquent builder.
     */
    public function export(CsvReservationWriter $writer): StreamedResponse
    {
        $query = QueryBuilder::for(FacilityReservation::class)
            ->allowedFilters($this->filters())
            ->allowedSorts($this->sorts())
            ->defaultSort('-booked_at')
            ->getEloquentBuilder();

        // Counted and audited before a byte is sent — see CsvReservationWriter::log().
        $writer->log((clone $query)->count());

        return $writer->stream($query);
    }

    /** The desk's own note. Everything else about a booking is a transition. */
    public function update(UpdateFacilityReservationData $data, FacilityReservation $facilityReservation): JsonResponse
    {
        $facilityReservation->update(['note' => $data->note]);

        return $this->respond(
            FacilityReservationData::from($facilityReservation->fresh()->load(['visitor', 'facility', 'slot'])),
            'Reservation note saved successfully',
        );
    }

    public function contact(FacilityReservation $facilityReservation): JsonResponse
    {
        return $this->transition($facilityReservation, Contacted::class, 'marked contacted', 'Reservation marked contacted');
    }

    public function confirm(FacilityReservation $facilityReservation): JsonResponse
    {
        return $this->transition($facilityReservation, Confirmed::class, 'confirmed', 'Reservation confirmed');
    }

    public function attend(FacilityReservation $facilityReservation): JsonResponse
    {
        return $this->transition($facilityReservation, Attended::class, 'marked attended', 'Reservation marked attended');
    }

    public function noShow(FacilityReservation $facilityReservation): JsonResponse
    {
        return $this->transition($facilityReservation, NoShow::class, 'marked a no-show', 'Reservation marked a no-show');
    }

    public function cancel(FacilityReservation $facilityReservation): JsonResponse
    {
        return $this->transition($facilityReservation, Cancelled::class, 'cancelled', 'Reservation cancelled');
    }

    /** Cancelled back into the queue — the one way a released seat is retaken. */
    public function reopen(FacilityReservation $facilityReservation): JsonResponse
    {
        return $this->transition($facilityReservation, Pending::class, 'reopened', 'Reservation reopened');
    }

    public function destroy(FacilityReservation $facilityReservation): JsonResponse
    {
        $facilityReservation->delete();

        return $this->respond(null, 'Booking deleted successfully');
    }

    /**
     * One transition, told the same way six times.
     *
     * The refusal is a 422 keyed on `status`, matching VisitReservationsController,
     * so the client reads the server's own wording out of ApiError.errors.status
     * rather than guessing which moves were legal.
     *
     * @param  class-string<\App\States\FacilityReservation\FacilityReservationStatus>  $target
     */
    protected function transition(FacilityReservation $reservation, string $target, string $verb, string $message): JsonResponse
    {
        try {
            $reservation->status->transitionTo($target);
        } catch (TransitionNotFound) {
            $from = $reservation->status->getValue();

            throw ValidationException::withMessages([
                'status' => ucfirst($this->article($from))." {$from} reservation cannot be {$verb}.",
            ]);
        }

        return $this->respond(
            FacilityReservationData::from($reservation->fresh()->load(['visitor', 'facility', 'slot'])),
            $message,
        );
    }

    /**
     * "a" or "an" for a status name.
     *
     * The jobs twin hardcodes "A", which reads fine there only because none of its
     * statuses begins with a vowel. `attended` does, and "A attended reservation"
     * is the kind of thing a reader notices before the sentence it is in.
     */
    protected function article(string $word): string
    {
        return in_array(substr($word, 0, 1), ['a', 'e', 'i', 'o', 'u'], true) ? 'an' : 'a';
    }

    /**
     * @return array<int, AllowedFilter>
     */
    protected function filters(): array
    {
        return [
            // REQUIRED: FacilityReservationObserver deep-links a notification straight
            // at one row with ?filter[id]=.
            AllowedFilter::exact('id'),
            AllowedFilter::exact('facility_id'),
            AllowedFilter::exact('facility_slot_id'),
            AllowedFilter::exact('visitor_id'),
            AllowedFilter::exact('status'),
            $this->date('booked_from', '>=', 'startOfDay', 'booked_at'),
            $this->date('booked_to', '<=', 'endOfDay', 'booked_at'),
            $this->searchGuardians(),
            $this->onDate(),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function sorts(): array
    {
        return ['id', 'status', 'booked_at', 'created_at'];
    }

    /**
     * Free text across the reservation id and the household behind it.
     *
     * concat_ws so "Sara Al Otaibi" matches a row whose first and last names are
     * stored apart — the same shape JobApplicationsController::searchApplicants()
     * uses, and for the same reason.
     */
    protected function searchGuardians(): AllowedFilter
    {
        return AllowedFilter::callback('search', function (Builder $query, mixed $value) {
            $term = '%'.$value.'%';

            $query->where(function (Builder $q) use ($term, $value) {
                $q->where('facility_reservations.id', $value)
                    ->orWhereHas('visitor', function (Builder $visitor) use ($term) {
                        $visitor->where('email', 'like', $term)
                            ->orWhere('phone', 'like', $term)
                            ->orWhere('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhereRaw("concat_ws(' ', first_name, last_name) like ?", [$term]);
                    });
            });
        });
    }

    /**
     * Everyone visiting on one day — the register, which is the question the desk
     * asks most often and the one a booked_at filter cannot answer, because when a
     * booking was MADE has nothing to do with when the visit HAPPENS.
     */
    protected function onDate(): AllowedFilter
    {
        return AllowedFilter::callback('visiting_on', function (Builder $query, mixed $value) {
            try {
                $date = Carbon::parse($value);
            } catch (InvalidFormatException) {
                // An unparsable date is a 422, never a silently dropped filter —
                // the same contract ApiController::date() keeps.
                throw ValidationException::withMessages([
                    'visiting_on' => __('validation.date', ['attribute' => 'visiting on']),
                ]);
            }

            $query->whereHas('slot', fn (Builder $slot) => $slot
                ->whereBetween('starts_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()]));
        });
    }
}
