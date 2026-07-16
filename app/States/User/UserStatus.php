<?php

namespace App\States\User;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Where an account sits in the approval workflow. Only Approved can sign in —
 * see App\Http\Controllers\Api\Admin\AuthController.
 *
 *  - Pending  : the default. Knocking at the door, nothing confirmed.
 *  - Verified : identity confirmed but not yet let in. Azure SSO lands here, so
 *               an SSO account still waits for an admin.
 *  - Approved : can sign in. Admin-created users start here — an admin creating
 *               the account IS the approval.
 *  - Rejected : refused. A dead end an admin can reopen by approving again.
 *
 * Rejected is reachable from every live state so access can always be revoked,
 * and Approved is reachable from every other so a decision is never final.
 */
abstract class UserStatus extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Pending::class)
            ->allowTransition(Pending::class, Verified::class)
            ->allowTransition(Pending::class, Approved::class)
            ->allowTransition(Pending::class, Rejected::class)
            ->allowTransition(Verified::class, Approved::class)
            ->allowTransition(Verified::class, Rejected::class)
            ->allowTransition(Approved::class, Rejected::class)
            ->allowTransition(Rejected::class, Approved::class);
    }
}
