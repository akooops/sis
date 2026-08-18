<?php

namespace App\States\VisitService;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Where a visit service sits editorially. Only Published is served.
 *
 *  - Draft     : the default. Being written, never announced.
 *  - Scheduled : waiting for published_at.
 *  - Published : live. published_at records when it went live.
 *  - Hidden    : was public and has been withdrawn. published_at is kept.
 *
 * The same shape and the same rationale as JobOfferStatus: Published is reachable
 * from everywhere so a service can always go back up, Hidden from anything that
 * was public so it can always come down, Published -> Draft is barred (taking a
 * live page down is an explicit Hidden, worth its own audit row) and so is
 * Draft -> Hidden (a draft was never public).
 *
 * Note this says nothing about whether anyone can BOOK. That is derived from the
 * service's slots, so hiding a service stops new bookings without touching the
 * reservations already made against it.
 */
abstract class VisitServiceStatus extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, Scheduled::class)
            ->allowTransition(Draft::class, Published::class)
            ->allowTransition(Scheduled::class, Draft::class)
            ->allowTransition(Scheduled::class, Published::class)
            ->allowTransition(Scheduled::class, Hidden::class)
            ->allowTransition(Published::class, Hidden::class)
            ->allowTransition(Published::class, Scheduled::class)
            ->allowTransition(Hidden::class, Draft::class)
            ->allowTransition(Hidden::class, Scheduled::class)
            ->allowTransition(Hidden::class, Published::class);
    }
}
