<?php

namespace App\Services\Legacy\Importers;

use App\Models\FacilityReservation;
use App\Models\VisitAttendee;
use App\Models\Visitor;
use App\Models\VisitReservation;
use App\Services\Legacy\LegacyImporter;
use Illuminate\Support\Facades\DB;

/**
 * Bookings, from both halves: school tours and venue hire.
 *
 * ── ONE PERSON, TWO KINDS OF BOOKING ─────────────────────────────────────────
 *
 * The old app kept the booker's name, email and phone ON each booking row, in two
 * unrelated tables, so a parent who booked a tour in September and the hall in
 * November was two strings that happened to match. This app has a `visitors`
 * table SHARED by both modules, matched on email OR phone — which is why Visitor
 * carries `visitReservations()` and `facilityReservations()` rather than a bare
 * `reservations()`.
 *
 * So the import runs both legacy tables through the same visitor() lookup, and
 * the first real dividend of the migration is that those two rows become one
 * contact record with both bookings under it.
 *
 * ── STATUS ───────────────────────────────────────────────────────────────────
 *
 * Neither legacy table had one: a row existed, and that was the whole state
 * machine. Everything therefore arrives as `pending`, which is the only status
 * that claims nothing. Marking historical bookings `attended` would invent a
 * register nobody kept, and `confirmed` would assert a call that may never have
 * happened. It does mean an install with years of history opens with a long
 * pending list, which the run says out loud.
 *
 * `cancelled` is deliberately not used as a tidy-up either: it is the one status
 * that gives a seat back, and a past booking did occupy its slot.
 */
class ReservationsImporter extends LegacyImporter
{
    protected int $visits = 0;

    protected int $hires = 0;

    public function module(): string
    {
        return 'reservations';
    }

    public function describe(): string
    {
        return 'Visit bookings and facility reservations';
    }

    public function dependsOn(): array
    {
        return ['visits', 'facilities'];
    }

    public function sources(): array
    {
        return [];
    }

    public function available(): bool
    {
        return $this->c->db->has('visit_bookings') || $this->c->db->has('facility_reservations');
    }

    public function run(): void
    {
        $this->visitBookings();
        $this->facilityReservations();

        if ($this->visits + $this->hires > 0) {
            $this->c->note(
                ($this->visits + $this->hires).' booking(s) arrived as `pending`, because neither legacy table '
                .'recorded a status. Past ones can be moved on in bulk from the admin; note that `cancelled` is the '
                .'one status that frees a seat.'
            );
        }
    }

    /** `visit_bookings` → Visitor + VisitReservation + VisitAttendee. */
    protected function visitBookings(): void
    {
        $this->each('visit_bookings', function (object $row) {
            $slotId = $this->c->map->find('visit_time_slots', $row->visit_time_slot_id ?? null);
            $serviceId = $this->c->map->find('visit_services', $row->visit_service_id ?? null);

            if (! $slotId || ! $serviceId) {
                $this->c->warn("Visit booking #{$row->id} references a slot or service that was not imported — skipped.");
                $this->c->skipped();

                return;
            }

            if ($this->c->dryRun) {
                $this->c->created();

                return;
            }

            DB::transaction(function () use ($row, $slotId, $serviceId) {
                $visitor = $this->visitor($row->guardian_name ?? null, $row->email ?? null, $row->phone ?? null, $row->created_at ?? null);

                // The unique pair (slot, visitor) is both the dedup rule and this
                // import's idempotency key — the same index the projector relies on.
                $reservation = VisitReservation::firstOrNew([
                    'visit_slot_id' => $slotId,
                    'visitor_id' => $visitor->id,
                ]);

                $reservation->visit_service_id = $serviceId;
                $reservation->status ??= 'pending';
                $reservation->booked_at ??= $row->created_at ?? now();

                // Two legacy bookings can fold onto one (slot, visitor) pair, so
                // the timestamp is a min rather than an assignment.
                $this->earliest($reservation, $row->created_at ?? null);

                $attendees = $this->students($row);

                /*
                 * The COUNT FOLLOWS THE ROWS, not the legacy `visitors_count`.
                 * That column was the ceiling the visitor picked; this app stores
                 * how many children were actually listed, which is what
                 * ReservationProjector computes before its first save for exactly
                 * the same reason.
                 */
                $reservation->visitors_count = max(1, count($attendees) ?: (int) ($row->visitors_count ?? 1));

                $this->save($reservation, 'visit_bookings', (int) $row->id);

                $this->attendees($reservation, $attendees);

                $this->visits++;
            });
        });
    }

    /** `facility_reservations` → Visitor + FacilityReservation. */
    protected function facilityReservations(): void
    {
        $this->reportDropped('facility_reservations');

        $this->each('facility_reservations', function (object $row) {
            $slotId = $this->c->map->find('facility_time_slots', $row->facility_time_slot_id ?? null);
            $facilityId = $this->c->map->find('facilities', $row->facility_id ?? null);

            if (! $slotId || ! $facilityId) {
                $this->c->warn("Facility reservation #{$row->id} references a slot or venue that was not imported — skipped.");
                $this->c->skipped();

                return;
            }

            if ($this->c->dryRun) {
                $this->c->created();

                return;
            }

            DB::transaction(function () use ($row, $slotId, $facilityId) {
                $visitor = $this->visitor($row->name ?? null, $row->email ?? null, $row->phone ?? null, $row->created_at ?? null);

                $reservation = FacilityReservation::firstOrNew([
                    'facility_slot_id' => $slotId,
                    'visitor_id' => $visitor->id,
                ]);

                $reservation->facility_id = $facilityId;
                $reservation->status ??= 'pending';
                $reservation->booked_at ??= $row->created_at ?? now();
                $reservation->note = $row->message ?: $reservation->note;

                $this->earliest($reservation, $row->created_at ?? null);

                $this->save($reservation, 'facility_reservations', (int) $row->id);

                $this->hires++;
            });
        });
    }

    /**
     * The household, shared by both modules.
     *
     * Matched on email OR phone through the model's own scope, so the same rule
     * that dedupes a live booking dedupes an imported one — and a family who
     * booked a tour with one address and the hall with another but the same
     * number is one record, which is the behaviour the visits module was
     * verified on.
     */
    protected function visitor(?string $fullName, ?string $email, ?string $phone, mixed $createdAt): Visitor
    {
        $email = trim((string) $email);
        $phone = $this->phone($phone);

        [$first, $last] = $this->splitName($fullName);

        $visitor = Visitor::query()->identifiedBy($email, $phone)->first() ?? new Visitor;

        // An existing visitor keeps the name already on file: it may have been
        // corrected in the admin, and the later booking is not more authoritative.
        if (! $visitor->exists) {
            $visitor->first_name = $first;
            $visitor->last_name = $last;
        }

        /*
         * Through identify(), for the reason it documents: `email` and `phone`
         * are each UNIQUE on `visitors` while identifiedBy() matches on EITHER,
         * so filling a blank phone from a booking found by email can take a
         * number another household already holds.
         */
        $this->identify($visitor, 'email', $email);
        $this->identify($visitor, 'phone', $phone);

        $this->earliest($visitor, $createdAt);

        $visitor->save();

        return $visitor;
    }

    /**
     * The `students` JSON column as attendee rows.
     *
     * The old shape is `[{name, grade, school}]` — one name box per child — and
     * this app stores first and last separately, so each is split the same way a
     * guardian's is.
     *
     * The column arrived late in the old app's life (it replaced three flat
     * `student_*` columns), so a dump from before that migration is read from
     * those instead.
     *
     * @return array<int, array{first: string, last: string, grade: ?string, school: ?string}>
     */
    protected function students(object $row): array
    {
        $raw = $row->students ?? null;
        $items = is_string($raw) ? json_decode($raw, true) : $raw;

        if (! is_array($items) || $items === []) {
            // The pre-JSON schema: one student, three columns.
            if (! empty($row->student_name)) {
                $items = [[
                    'name' => $row->student_name,
                    'grade' => $row->student_grade ?? null,
                    'school' => $row->student_school ?? null,
                ]];
            } else {
                return [];
            }
        }

        $out = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            [$first, $last] = $this->splitName($item['name'] ?? null);

            $out[] = [
                'first' => $first,
                'last' => $last,
                'grade' => $item['grade'] ?? null,
                'school' => $item['school'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * Replace this reservation's attendees.
     *
     * Wholesale, for the reason the projectors give: a child in a repeat has no
     * stable id, so there is nothing to diff against. VisitAttendee is one of the
     * models this app deliberately leaves UNOBSERVED precisely because it is
     * rewritten this way.
     *
     * @param  array<int, array{first: string, last: string, grade: ?string, school: ?string}>  $attendees
     */
    protected function attendees(VisitReservation $reservation, array $attendees): void
    {
        if ($attendees === []) {
            return;
        }

        VisitAttendee::where('visit_reservation_id', $reservation->id)->delete();

        foreach ($attendees as $order => $attendee) {
            VisitAttendee::create([
                'visit_reservation_id' => $reservation->id,
                'first_name' => $attendee['first'],
                'last_name' => $attendee['last'],
                'grade' => $attendee['grade'] ?: null,
                'current_school' => $attendee['school'] ?: null,
                'order' => $order,
            ]);
        }
    }
}
