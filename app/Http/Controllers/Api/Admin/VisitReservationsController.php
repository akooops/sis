<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\VisitReservation\UpdateVisitReservationData;
use App\Data\VisitReservation\VisitReservationData;
use App\Http\Controllers\Api\ApiController;
use App\Models\VisitReservation;
use App\Services\Visits\CsvReservationWriter;
use App\States\VisitReservation\Attended;
use App\States\VisitReservation\Cancelled;
use App\States\VisitReservation\Confirmed;
use App\States\VisitReservation\Contacted;
use App\States\VisitReservation\NoShow;
use App\States\VisitReservation\Pending;
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
class VisitReservationsController extends ApiController
{
    public function index(): JsonResponse
    {
        $reservations = QueryBuilder::for(VisitReservation::class)
            ->with(['visitor', 'service', 'slot'])
            ->allowedFilters($this->filters())
            ->allowedSorts($this->sorts())
            // Nullable, and MySQL sorts NULL last on DESC, so a row the projector
            // never stamped falls to the bottom rather than heading the queue.
            ->defaultSort('-booked_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(VisitReservationData::collect($reservations, PaginatedDataCollection::class), 'Visit reservations retrieved successfully');
    }

    public function show(VisitReservation $visitReservation): JsonResponse
    {
        $visitReservation->load(['visitor', 'service', 'slot', 'attendees', 'submission']);

        return $this->respond(VisitReservationData::from($visitReservation), 'Visit reservation retrieved successfully');
    }

    /**
     * The CSV, under the same filters the list is showing.
     *
     * getEloquentBuilder() is the ONLY way to hand the filtered query on: a
     * QueryBuilder is not an Eloquent builder.
     */
    public function export(CsvReservationWriter $writer): StreamedResponse
    {
        $query = QueryBuilder::for(VisitReservation::class)
            ->allowedFilters($this->filters())
            ->allowedSorts($this->sorts())
            ->defaultSort('-booked_at')
            ->getEloquentBuilder();

        // Counted and audited before a byte is sent — see CsvReservationWriter::log().
        $writer->log((clone $query)->count());

        return $writer->stream($query);
    }

    /** The desk's own note. Everything else about a booking is a transition. */
    public function update(UpdateVisitReservationData $data, VisitReservation $visitReservation): JsonResponse
    {
        $visitReservation->update(['note' => $data->note]);

        return $this->respond(
            VisitReservationData::from($visitReservation->fresh()->load(['visitor', 'service', 'slot'])),
            'Reservation note saved successfully',
        );
    }

    public function contact(VisitReservation $visitReservation): JsonResponse
    {
        return $this->transition($visitReservation, Contacted::class, 'marked contacted', 'Reservation marked contacted');
    }

    public function confirm(VisitReservation $visitReservation): JsonResponse
    {
        return $this->transition($visitReservation, Confirmed::class, 'confirmed', 'Reservation confirmed');
    }

    public function attend(VisitReservation $visitReservation): JsonResponse
    {
        return $this->transition($visitReservation, Attended::class, 'marked attended', 'Reservation marked attended');
    }

    public function noShow(VisitReservation $visitReservation): JsonResponse
    {
        return $this->transition($visitReservation, NoShow::class, 'marked a no-show', 'Reservation marked a no-show');
    }

    public function cancel(VisitReservation $visitReservation): JsonResponse
    {
        return $this->transition($visitReservation, Cancelled::class, 'cancelled', 'Reservation cancelled');
    }

    /** Cancelled back into the queue — the one way a released seat is retaken. */
    public function reopen(VisitReservation $visitReservation): JsonResponse
    {
        return $this->transition($visitReservation, Pending::class, 'reopened', 'Reservation reopened');
    }

    public function destroy(VisitReservation $visitReservation): JsonResponse
    {
        $visitReservation->delete();

        return $this->respond(null, 'Visit reservation deleted successfully');
    }

    /**
     * One transition, told the same way six times.
     *
     * The refusal is a 422 keyed on `status`, matching JobApplicationsController,
     * so the client reads the server's own wording out of ApiError.errors.status
     * rather than guessing which moves were legal.
     *
     * @param  class-string<\App\States\VisitReservation\VisitReservationStatus>  $target
     */
    protected function transition(VisitReservation $reservation, string $target, string $verb, string $message): JsonResponse
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
            VisitReservationData::from($reservation->fresh()->load(['visitor', 'service', 'slot'])),
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
            // REQUIRED: VisitReservationObserver deep-links a notification straight
            // at one row with ?filter[id]=.
            AllowedFilter::exact('id'),
            AllowedFilter::exact('visit_service_id'),
            AllowedFilter::exact('visit_slot_id'),
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
        return ['id', 'status', 'booked_at', 'visitors_count', 'created_at'];
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
                $q->where('visit_reservations.id', $value)
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
