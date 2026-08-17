<?php

namespace App\States\JobApplication;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Where an application has reached in HR's hands.
 *
 *  - Received    : the default. It arrived; nobody has looked yet.
 *  - Shortlisted : passed screening and is being put in front of a manager.
 *  - Contacted   : someone has written to them.
 *  - Called      : someone has spoken to them.
 *  - Hired       : terminal, and the only happy end.
 *  - Rejected    : terminal in practice, but reversible — see below.
 *
 * NAMED `Received`, NOT `New`, because `new` is a reserved word in PHP and cannot
 * be a class name. The stored value is 'received' to match.
 *
 * FORWARD, PLUS REJECT FROM ANYWHERE. Every live state can be rejected, because a
 * candidate can drop out at any point and HR should never have to walk one
 * through three stages to close it. Rejected -> Shortlisted is the one way back:
 * people are rejected by mistake, and a reopen leaves an audit row where deleting
 * and re-entering the application would leave nothing.
 *
 * Contacted -> Hired and Called -> Hired both exist because a small school does
 * hire off a conversation without a formal stage in between; forcing the ladder
 * would only teach everyone to lie to the tracker.
 *
 * There is no route back OUT of Hired. Undoing a hire is a decision with
 * paperwork attached, not a dropdown.
 */
abstract class JobApplicationStatus extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Received::class)

            ->allowTransition(Received::class, Shortlisted::class)
            ->allowTransition(Received::class, Rejected::class)

            ->allowTransition(Shortlisted::class, Contacted::class)
            ->allowTransition(Shortlisted::class, Rejected::class)

            ->allowTransition(Contacted::class, Called::class)
            ->allowTransition(Contacted::class, Hired::class)
            ->allowTransition(Contacted::class, Rejected::class)

            ->allowTransition(Called::class, Hired::class)
            ->allowTransition(Called::class, Rejected::class)

            ->allowTransition(Rejected::class, Shortlisted::class);
    }
}
