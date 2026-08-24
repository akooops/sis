<?php

namespace App\Rules;

use App\Contracts\Forms\SubmissionRule;
use App\Models\Facility;
use App\Models\FacilityReservation;
use App\Models\FacilitySlot;
use App\Models\Form;
use App\Models\Visitor;

/**
 * The two things a venue booking must satisfy that no single FIELD can check.
 *
 *  1. the slot exists, belongs to the chosen venue, and can still be taken;
 *  2. this person has not already booked that slot.
 *
 * Its visits twin has a third — the students group may not exceed the party size
 * — which has no counterpart here, because a venue booking is one person and one
 * seat.
 *
 * WHY RULES AND NOT CHECKS IN THE PROJECTOR. The projector runs after the
 * response has gone, so a failure there is silent: the visitor is thanked and
 * nothing is created. As rules, each lands under an input they can fix, in their
 * own language, before anything is stored.
 *
 * The unique index on (facility_slot_id, visitor_id) is still the real guarantee
 * for the second check — two simultaneous submits could both pass and only one
 * would survive the insert. This exists to make the common case a readable
 * sentence rather than a constraint violation.
 */
class FacilityReservationIsAllowed implements SubmissionRule
{
    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, array<int, mixed>>
     */
    public function rules(Form $form, array $answers): array
    {
        $slotKey = config('facilities.slot_field');
        $facilityKey = config('facilities.facility_field');
        $emailKey = $this->keyFor('email');
        $phoneKey = $this->keyFor('phone');

        $slot = $this->slot($answers[$slotKey] ?? null);
        $facility = $this->facility($answers[$facilityKey] ?? null);

        return [
            "fields.{$slotKey}" => [$this->slotIsBookable($slot, $facility)],
            "fields.{$emailKey}" => [$this->notAlreadyBooked($slot, $answers, $emailKey, $phoneKey)],
        ];
    }

    /**
     * The slot must exist, belong to the venue the visitor is looking at, and
     * still be takeable.
     *
     * RE-CHECKED HERE because both ids are hidden inputs: a visitor can put
     * anything in them, including a time that filled up or was closed while the
     * page sat open in a tab — which, on a booking page, is the normal case rather
     * than an attack.
     */
    protected function slotIsBookable(?FacilitySlot $slot, ?Facility $facility): callable
    {
        return function (string $attribute, mixed $value, callable $fail) use ($slot, $facility) {
            if (! $slot) {
                $fail(__('forms.facility_slot_missing'));

                return;
            }

            // Belonging is checked before state: a time that is open but attached
            // to another venue is a mismatched booking, not a full one, and saying
            // "fully booked" there would send the visitor hunting for another time.
            if ($facility && $slot->facility_id !== $facility->id) {
                $fail(__('forms.facility_slot_mismatched'));

                return;
            }

            match ($slot->state()) {
                'closed' => $fail(__('forms.facility_slot_closed')),
                'full' => $fail(__('forms.facility_slot_full')),
                default => null,
            };
        };
    }

    /**
     * One booking per person per time.
     *
     * The person is matched the SAME way the projector matches them — email or
     * phone, never both — or the rule would pass someone the projector then
     * recognises as an existing visitor, and the unique index would reject the
     * insert after they had already been thanked.
     *
     * Attached to the EMAIL field because that is an identifier they can see and
     * change; pointing it at the hidden slot id would show a message with nothing
     * under it. A CANCELLED booking does not count — someone who cancelled and
     * changed their mind must be able to book again.
     *
     * @param  array<string, mixed>  $answers
     */
    protected function notAlreadyBooked(?FacilitySlot $slot, array $answers, string $emailKey, string $phoneKey): callable
    {
        return function (string $attribute, mixed $value, callable $fail) use ($slot, $answers, $emailKey, $phoneKey) {
            if (! $slot) {
                return;
            }

            $visitor = Visitor::query()
                ->identifiedBy($answers[$emailKey] ?? null, $answers[$phoneKey] ?? null)
                ->first();

            if (! $visitor) {
                return;
            }

            $booked = FacilityReservation::query()
                ->active()
                ->where('facility_slot_id', $slot->id)
                ->where('visitor_id', $visitor->id)
                ->exists();

            if ($booked) {
                $fail(__('forms.facility_already_booked'));
            }
        };
    }

    protected function slot(mixed $id): ?FacilitySlot
    {
        return is_string($id) && $id !== '' ? FacilitySlot::find($id) : null;
    }

    protected function facility(mixed $id): ?Facility
    {
        return is_string($id) && $id !== '' ? Facility::find($id) : null;
    }

    /**
     * The FORM key feeding one visitors column.
     *
     * Read off config('facilities.fields') rather than hardcoded, so renaming a
     * question moves this rule with it — the map is the single statement of that
     * vocabulary, and a second copy here would be the thing that goes stale.
     */
    protected function keyFor(string $column): string
    {
        return (string) (array_search($column, config('facilities.fields', []), true) ?: $column);
    }
}
