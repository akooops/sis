<?php

namespace App\Services\Visits;

use App\Contracts\Forms\SubmissionProjector;
use App\Models\FormSubmission;
use App\Models\VisitReservation;
use App\Models\VisitSlot;
use App\Models\Visitor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Turns a completed reservation submission into domain rows.
 *
 * THE SEAM, USED A SECOND TIME. Forms own how a booking was collected — the
 * builder-rendered fields, the honeypot, the captcha, the country and IP blocks,
 * the per-visitor caps, nine locales. This owns what it MEANS: a household, the
 * fact that they booked a particular time, and who is coming with them. The raw
 * answers stay on the FormSubmission and the reservation points back at it, so
 * this can be re-run at any time without consuming the original.
 *
 * IDEMPOTENT BY CONSTRUCTION. Visitors are matched on email or phone, the
 * reservation on (slot, visitor) — which is its unique index — and the attendees
 * are replaced wholesale rather than appended. Running it twice over one
 * submission produces the same rows, and re-running it after a map change repairs
 * old data instead of duplicating it.
 *
 * THE OVERBOOKING GAP, AND WHY IT IS LEFT OPEN. VisitReservationIsAllowed checks
 * capacity inside the request; this runs after the response has gone. Two people
 * can take the last seat in between. This locks the slot and re-counts, and when
 * it finds the slot over capacity it STILL WRITES THE RESERVATION and logs it:
 * the visitor has already been shown a confirmation, and silently voiding a
 * booking somebody believes they hold is worse than a slot the admissions desk
 * has to look at. The admin calendar renders reserved/capacity and badges an
 * over-capacity slot, so a person resolves it.
 */
class ReservationProjector implements SubmissionProjector
{
    public function project(FormSubmission $submission): mixed
    {
        // Only a completed submission is a booking. A spam row, an abandoned
        // draft or one that failed validation is not one, and the
        // fake-confirmation path deliberately writes a `spam` row that must never
        // become a household.
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
            $slot = VisitSlot::query()->whereKey($slot->getKey())->lockForUpdate()->first();

            if (! $slot) {
                return null;
            }

            $visitor = $this->visitor($data);

            $reservation = VisitReservation::firstOrNew([
                'visit_slot_id' => $slot->getKey(),
                'visitor_id' => $visitor->getKey(),
            ]);

            $isNew = ! $reservation->exists;

            // ALWAYS FROM THE SLOT, never from the submitted service answer: the
            // two would otherwise be able to disagree, and the slot is the thing
            // that was actually booked.
            $reservation->visit_service_id = $slot->visit_service_id;
            $reservation->form_submission_id = $submission->getKey();
            // First booking wins the timestamp: a re-run must not make an old
            // reservation look like it arrived today and jump the queue.
            $reservation->booked_at ??= $submission->submitted_at ?? now();

            /*
             * The headcount is worked out BEFORE the save, not after the attendees
             * are written, so the row is written once.
             *
             * Saving it a second time to correct the count produced a second audit
             * row on every booking that said nothing but "1 became 2", and left the
             * `created` row claiming a party of one.
             *
             * It is the number of children actually listed, not the hidden
             * `visitors_count` answer — that is the ceiling the visitor picked on
             * the card, which the submit-time rule enforces as a max and nothing
             * stores.
             */
            $groups = $this->attendeeRows($data);
            $reservation->visitors_count = max(1, array_sum(array_map('count', $groups)));
            $reservation->save();

            $this->writeAttendees($reservation, $groups);

            $this->warnIfOverbooked($slot, $isNew);

            return $reservation;
        });
    }

    /**
     * The slot booked, from the hidden field.
     *
     * RE-VERIFIED, NEVER TRUSTED: the field is a hidden input and a visitor can
     * put anything in it. Unlike the submit-time rule this does NOT refuse a
     * closed or full slot — by the time a re-projection runs, months later, every
     * slot it ever touched is in the past, and refusing them would make old
     * bookings unrepairable.
     *
     * @param  array<string, mixed>  $data
     */
    protected function slot(array $data): ?VisitSlot
    {
        $id = $data[config('visits.slot_field')] ?? null;

        return is_string($id) && $id !== '' ? VisitSlot::find($id) : null;
    }

    /**
     * The household, found or created.
     *
     * EMAIL OR PHONE, not both — see Visitor::scopeIdentifiedBy. The phone was
     * normalised to E164 by PhoneType::store() at submit, so the comparison is
     * against the same shape every time.
     *
     * @param  array<string, mixed>  $data
     */
    protected function visitor(array $data): Visitor
    {
        $attributes = [];

        foreach (config('visits.fields') as $answerKey => $column) {
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
     * The students coming along, mapped onto columns and filtered — but not yet
     * written, because the count of them decides a column on the reservation those
     * rows hang off.
     *
     * @param  array<string, mixed>  $data
     * @return array<class-string<\Illuminate\Database\Eloquent\Model>, array<int, array<string, mixed>>>
     */
    protected function attendeeRows(array $data): array
    {
        $groups = [];

        foreach (config('visits.groups') as $answerKey => $spec) {
            $rows = $data[$answerKey] ?? null;

            if (! is_array($rows)) {
                continue;
            }

            /** @var class-string<\Illuminate\Database\Eloquent\Model> $model */
            $model = $spec['table'];

            $groups[$model] ??= [];

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $attributes = [];

                foreach ($spec['map'] as $childKey => $column) {
                    if (array_key_exists($childKey, $row)) {
                        $attributes[$column] = $row[$childKey];
                    }
                }

                // A row with nothing in it is not a person. The renderer already
                // drops blank repeats and the validator agrees, so this only
                // catches a hand-rolled post.
                if (array_filter($attributes, fn ($value) => $value !== null && $value !== '') === []) {
                    continue;
                }

                $groups[$model][] = $attributes;
            }
        }

        return $groups;
    }

    /**
     * Write them — replaced wholesale.
     *
     * DELETE THEN INSERT rather than diffing: a repeat row is identified only by
     * its position in the answer, so there is nothing to match an existing child
     * against. Replacing is the only operation that is both correct and
     * idempotent.
     *
     * `order` is reassigned from zero here rather than carried from the answer,
     * because a blank row dropped above would otherwise leave a gap.
     *
     * @param  array<class-string<\Illuminate\Database\Eloquent\Model>, array<int, array<string, mixed>>>  $groups
     */
    protected function writeAttendees(VisitReservation $reservation, array $groups): void
    {
        foreach ($groups as $model => $rows) {
            $model::query()->where('visit_reservation_id', $reservation->getKey())->delete();

            foreach ($rows as $order => $attributes) {
                $model::create($attributes + [
                    'visit_reservation_id' => $reservation->getKey(),
                    'order' => $order,
                ]);
            }
        }
    }

    /**
     * Say so, loudly and once, when the race described in the class docblock
     * actually happened.
     *
     * Only for a NEW reservation: a re-projection of an old booking into a slot
     * that is over-subscribed for unrelated reasons is not news, and would write
     * this line every time the projector was re-run.
     */
    protected function warnIfOverbooked(VisitSlot $slot, bool $isNew): void
    {
        if (! $isNew) {
            return;
        }

        $taken = $slot->reservations()->active()->count();

        if ($taken <= (int) $slot->capacity) {
            return;
        }

        Log::channel('integrations')->warning('visits.slot-overbooked', [
            'slot' => $slot->getKey(),
            'service' => $slot->visit_service_id,
            'capacity' => (int) $slot->capacity,
            'reserved' => $taken,
        ]);
    }
}
