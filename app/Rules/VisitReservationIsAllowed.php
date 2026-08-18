<?php

namespace App\Rules;

use App\Contracts\Forms\SubmissionRule;
use App\Models\Form;
use App\Models\VisitReservation;
use App\Models\VisitService;
use App\Models\VisitSlot;
use App\Models\Visitor;

/**
 * The three things a visit reservation must satisfy that no single FIELD can check.
 *
 *  1. the slot exists, belongs to the chosen service, and can still be taken;
 *  2. this household has not already booked that slot;
 *  3. no more students were listed than the party size the visitor chose.
 *
 * WHY RULES AND NOT CHECKS IN THE PROJECTOR. The projector runs after the response
 * has gone, so a failure there is silent: the visitor is thanked and nothing is
 * created. As rules, each lands under an input they can actually fix, in their own
 * language, before anything is stored.
 *
 * The unique index on (visit_slot_id, visitor_id) is still the real guarantee for
 * the second check — two simultaneous submits could both pass and only one would
 * survive the insert. This exists to make the common case a readable sentence
 * rather than a constraint violation.
 *
 * Capacity is the one check with no index behind it, because capacity is a count
 * and not a uniqueness property. See ReservationProjector for what happens in the
 * gap.
 */
class VisitReservationIsAllowed implements SubmissionRule
{
    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, array<int, mixed>>
     */
    public function rules(Form $form, array $answers): array
    {
        $slotKey = config('visits.slot_field');
        $serviceKey = config('visits.service_field');
        $studentsKey = $this->groupKey();
        $emailKey = $this->keyFor('email');
        $phoneKey = $this->keyFor('phone');

        $slot = $this->slot($answers[$slotKey] ?? null);
        $service = $this->service($answers[$serviceKey] ?? null);

        return [
            "fields.{$slotKey}" => [$this->slotIsBookable($slot, $service)],
            "fields.{$emailKey}" => [$this->notAlreadyBooked($slot, $answers, $emailKey, $phoneKey)],
            "fields.{$studentsKey}" => $this->partySizeRules($answers, $service),
        ];
    }

    /**
     * The slot must exist, belong to the service the visitor picked, and still be
     * takeable.
     *
     * RE-CHECKED HERE because both ids are hidden inputs: a visitor can put
     * anything in them, including a slot that filled up or was closed while the
     * page sat open in a tab — which, on a booking page, is the normal case rather
     * than an attack.
     */
    protected function slotIsBookable(?VisitSlot $slot, ?VisitService $service): callable
    {
        return function (string $attribute, mixed $value, callable $fail) use ($slot, $service) {
            if (! $slot) {
                $fail(__('forms.visit_slot_missing'));

                return;
            }

            // Belonging is checked before state: a slot that is open but attached
            // to another service is a mismatched booking, not a full one, and
            // saying "fully booked" there would send the visitor hunting.
            if ($service && $slot->visit_service_id !== $service->id) {
                $fail(__('forms.visit_slot_mismatched'));

                return;
            }

            match ($slot->state()) {
                'closed' => $fail(__('forms.visit_slot_closed')),
                'full' => $fail(__('forms.visit_slot_full')),
                default => null,
            };
        };
    }

    /**
     * One booking per household per slot.
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
    protected function notAlreadyBooked(?VisitSlot $slot, array $answers, string $emailKey, string $phoneKey): callable
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

            $booked = VisitReservation::query()
                ->active()
                ->where('visit_slot_id', $slot->id)
                ->where('visitor_id', $visitor->id)
                ->exists();

            if ($booked) {
                $fail(__('forms.visit_already_booked'));
            }
        };
    }

    /**
     * No more students than the party size chosen on the card.
     *
     * A MAX, NOT AN EQUALITY. The counter sets the ceiling of the repeatable
     * group; a visitor who picks four and then removes a row has changed their
     * mind, not made a mistake, and the reservation records the number of rows
     * they actually filled. The ceiling is clamped to the service, so a tampered
     * hidden field cannot buy extra rows.
     *
     * @param  array<string, mixed>  $answers
     * @return array<int, mixed>
     */
    protected function partySizeRules(array $answers, ?VisitService $service): array
    {
        $declared = (int) ($answers[config('visits.visitors_field')] ?? 0);
        $ceiling = $service ? $service->maxVisitors() : 0;

        if ($declared < 1 || $ceiling < 1) {
            return [];
        }

        return ['max:'.min($declared, $ceiling)];
    }

    protected function slot(mixed $id): ?VisitSlot
    {
        return is_string($id) && $id !== '' ? VisitSlot::find($id) : null;
    }

    protected function service(mixed $id): ?VisitService
    {
        return is_string($id) && $id !== '' ? VisitService::find($id) : null;
    }

    /**
     * The FORM key feeding one visitors column.
     *
     * Read off config('visits.fields') rather than hardcoded, so renaming a
     * question moves this rule with it — the map is the single statement of that
     * vocabulary, and a second copy here would be the thing that goes stale.
     */
    protected function keyFor(string $column): string
    {
        return (string) (array_search($column, config('visits.fields', []), true) ?: $column);
    }

    /** The one repeatable group, by its answer key. */
    protected function groupKey(): string
    {
        return (string) (array_key_first(config('visits.groups', [])) ?: 'students');
    }
}
