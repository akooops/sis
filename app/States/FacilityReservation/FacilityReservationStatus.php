<?php

namespace App\States\FacilityReservation;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Where a venue booking has reached at the desk.
 *
 *  - Pending   : the default. It arrived; nobody has looked yet.
 *  - Contacted : someone has rung or written to them.
 *  - Confirmed : the time is held and they have committed.
 *  - Attended  : terminal. They came.
 *  - NoShow    : they did not.
 *  - Cancelled : the seat is released — the one status that gives capacity back.
 *
 * Identical to VisitReservationStatus, deliberately: the two queues sit side by
 * side in the admin and a desk that has learned one must not have to learn the
 * other. The old facilities module had NO statuses at all, so a booking was
 * created and could only be deleted.
 *
 * Pending -> Confirmed skips Contacted on purpose: a desk that recognises the
 * name confirms it without ringing first, and forcing the ladder would only teach
 * everyone to lie to the tracker. Cancel is reachable from every live state.
 *
 * TWO WAYS BACK: Cancelled -> Pending puts a wrongly-cancelled booking back in
 * the queue, and NoShow -> Attended fixes a register marked before the last
 * person walked in. There is no route out of Attended: the visit happened.
 */
abstract class FacilityReservationStatus extends State
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
