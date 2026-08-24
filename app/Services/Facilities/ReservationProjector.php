<?php

namespace App\Services\Facilities;

use App\Contracts\Forms\SubmissionProjector;
use App\Models\FacilityReservation;
use App\Models\FacilitySlot;
use App\Models\FormSubmission;
use App\Models\Visitor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Turns a completed venue-booking submission into domain rows.
 *
 * THE SEAM, USED A THIRD TIME — after job applications and visit reservations.
 * Forms own how the booking was collected: the builder-rendered fields, the
 * honeypot, the captcha, the country and IP blocks, the per-visitor caps, nine
 * locales. This owns what it MEANS: a person, and the fact that they hold a
 * particular time at a particular venue.
 *
 * SIMPLER THAN ITS VISITS TWIN. There is no party size and there are no attendee
 * children, so the whole `attendeeRows()`/`writeAttendees()` half is absent. What
 * is left is the person and the booking.
 *
 * IDEMPOTENT BY CONSTRUCTION. The visitor is matched on email or phone, the
 * reservation on (slot, visitor) — which is its unique index — so running it
 * twice over one submission produces the same rows, and re-running it after a map
 * change repairs old data instead of duplicating it.
 *
 * THE OVERBOOKING GAP IS LEFT OPEN ON PURPOSE, exactly as in the visits twin.
 * FacilityReservationIsAllowed checks capacity inside the request; this runs after
 * the response has gone, so two people can take the last seat in between. This
 * locks the slot and re-counts, and when it finds the slot over capacity it STILL
 * WRITES THE RESERVATION and logs it: the visitor has already been shown a
 * confirmation, and silently voiding a booking somebody believes they hold is
 * worse than a slot the desk has to look at. The admin calendar rings an
 * over-capacity slot so a person resolves it.
 */
class ReservationProjector implements SubmissionProjector
{
    public function project(FormSubmission $submission): mixed
    {
        // Only a completed submission is a booking. A spam row, an abandoned
        // draft or one that failed validation is not one, and the
        // fake-confirmation path deliberately writes a `spam` row that must never
        // become a person.
        if ($submission->status !== 'completed') {
            return null;
        }

        $data = $submission->data ?? [];
        $slot = $this->slot($data);

        if (! $slot) {
            return null;
        }

        return DB::transaction(function () use ($submission, $data, $slot) {
            // Re-read under a row lock so two projections against the same slot
            // serialise rather than interleave their counts.
            $slot = FacilitySlot::query()->whereKey($slot->getKey())->lockForUpdate()->first();

            if (! $slot) {
                return null;
            }

            $visitor = $this->visitor($data);

            $reservation = FacilityReservation::firstOrNew([
                'facility_slot_id' => $slot->getKey(),
                'visitor_id' => $visitor->getKey(),
            ]);

            $isNew = ! $reservation->exists;

            // ALWAYS FROM THE SLOT, never from the submitted facility answer: the
            // two would otherwise be able to disagree, and the slot is the thing
            // that was actually booked.
            $reservation->facility_id = $slot->facility_id;
            $reservation->form_submission_id = $submission->getKey();
            // First booking wins the timestamp: a re-run must not make an old
            // reservation look like it arrived today and jump the queue.
            $reservation->booked_at ??= $submission->submitted_at ?? now();
            $reservation->save();

            $this->warnIfOverbooked($slot, $isNew);

            return $reservation;
        });
    }

    /**
     * The slot booked, from the hidden field.
     *
     * RE-VERIFIED, NEVER TRUSTED. Unlike the submit-time rule this does NOT refuse
     * a closed or full slot — by the time a re-projection runs, months later,
     * every slot it ever touched is in the past, and refusing them would make old
     * bookings unrepairable.
     *
     * @param  array<string, mixed>  $data
     */
    protected function slot(array $data): ?FacilitySlot
    {
        $id = $data[config('facilities.slot_field')] ?? null;

        return is_string($id) && $id !== '' ? FacilitySlot::find($id) : null;
    }

    /**
     * The person, found or created — in the SHARED `visitors` table.
     *
     * EMAIL OR PHONE, not both, which is what lets somebody who booked a school
     * tour last term book the hall today without becoming a second record. The
     * phone was normalised to E164 by PhoneType::store() at submit, so the
     * comparison is against the same shape every time.
     *
     * @param  array<string, mixed>  $data
     */
    protected function visitor(array $data): Visitor
    {
        $attributes = [];

        foreach (config('facilities.fields') as $answerKey => $column) {
            $value = $data[$answerKey] ?? null;

            if ($value !== null && $value !== '') {
                $attributes[$column] = is_string($value) ? trim($value) : $value;
            }
        }

        $visitor = Visitor::query()
            ->identifiedBy($attributes['email'] ?? null, $attributes['phone'] ?? null)
            ->first() ?? new Visitor;

        $visitor->fill($attributes);
        $visitor->save();

        return $visitor;
    }

    /**
     * Say so, loudly and once, when the race described in the class docblock
     * actually happened.
     *
     * Only for a NEW reservation: a re-projection of an old booking into a slot
     * that is over-subscribed for unrelated reasons is not news, and would write
     * this line every time the projector was re-run.
     */
    protected function warnIfOverbooked(FacilitySlot $slot, bool $isNew): void
    {
        if (! $isNew) {
            return;
        }

        $taken = $slot->reservations()->active()->count();

        if ($taken <= (int) $slot->capacity) {
            return;
        }

        Log::channel('integrations')->warning('facilities.slot-overbooked', [
            'slot' => $slot->getKey(),
            'facility' => $slot->facility_id,
            'capacity' => (int) $slot->capacity,
            'reserved' => $taken,
        ]);
    }
}
