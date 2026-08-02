<?php

namespace App\States\Form;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Where a form sits editorially. Only Published is served publicly.
 *
 *  - Draft     : the default. Being built, never reachable.
 *  - Scheduled : waiting for published_at; forms:publish-scheduled flips it.
 *  - Published : live and accepting submissions. published_at records when.
 *  - Hidden    : was public and has been withdrawn. published_at is kept.
 *
 * The same graph as every other editorial module, deliberately: Published is
 * reachable from everywhere so a form can always go back up, and Hidden from
 * anything that was public so it can always come down. Published -> Draft is
 * barred (taking a live form down is an explicit Hidden, worth its own audit
 * row) and so is Draft -> Hidden (a draft was never public).
 *
 * Note this is the FORM's status, not a submission's. A submission carries a
 * plain string instead — see the note on the form_submissions migration.
 */
abstract class FormStatus extends State
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
