<?php

namespace App\States\VisitReservation;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Where a booking has reached at the admissions desk.
 *
 *  - Pending   : the default. It arrived; nobody has looked yet.
 *  - Contacted : someone has rung or written to the guardian.
 *  - Confirmed : the seat is held and the family has committed.
 *  - Attended  : terminal. They came.
 *  - NoShow    : they did not.
 *  - Cancelled : the seat is released — see below, this is the one status that
 *                gives capacity back.
 *
 * The old app had NO statuses at all: a booking was created and the only thing
 * anyone could do to it was delete it. There was no way to record that the
 * guardian had been reached, no way to hold a seat, and no attendance record.
 *
 * CANCELLED IS THE ONLY STATUS THAT FREES A SEAT (VisitReservation::scopeActive).
 * Everything else holds one, including NoShow — the seat was genuinely occupied
 * on the day, and re-opening a past slot helps nobody.
 *
 * Pending -> Confirmed skips Contacted on purpose: a desk that recognises the
 * name confirms it without ringing first, and forcing the ladder would only teach
 * everyone to lie to the tracker. Cancel is reachable from every live state
 * because a family can drop out at any point.
 *
 * TWO WAYS BACK, both because the mistake they undo is common and silent:
 * Cancelled -> Pending puts a wrongly-cancelled booking back in the queue, and
 * NoShow -> Attended fixes a register marked before the last family walked in.
 * Both leave an audit row where deleting and re-entering would leave nothing.
 * There is no route out of Attended: the visit happened.
 */
abstract class VisitReservationStatus extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Pending::class)

            ->allowTransition(Pending::class, Contacted::class)
            ->allowTransition(Pending::class, Confirmed::class)
            ->allowTransition(Pending::class, Cancelled::class)

            ->allowTransition(Contacted::class, Confirmed::class)
            ->allowTransition(Contacted::class, Cancelled::class)

            ->allowTransition(Confirmed::class, Attended::class)
            ->allowTransition(Confirmed::class, NoShow::class)
            ->allowTransition(Confirmed::class, Cancelled::class)

            ->allowTransition(Cancelled::class, Pending::class)
            ->allowTransition(NoShow::class, Attended::class);
    }
}
