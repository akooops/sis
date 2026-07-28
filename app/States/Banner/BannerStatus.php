<?php

namespace App\States\Banner;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Where a banner sits editorially. Only Published is served.
 *
 *  - Draft     : the default. Being prepared, never shown.
 *  - Scheduled : waiting for published_at; banners:publish-scheduled flips it.
 *  - Published : live in the carousel. published_at records when it went live.
 *  - Hidden    : was public and has been withdrawn. published_at is kept.
 *
 * Published is reachable from everywhere so a banner can always go back up, and
 * Hidden from anything that was public so it can always come down. Published ->
 * Draft is barred (taking a live banner down is an explicit Hidden, worth its own
 * audit row) and so is Draft -> Hidden (a draft was never public).
 *
 * Status is editorial, `order` is presentation: a hidden banner keeps its position
 * so re-publishing puts it back where it was.
 */
abstract class BannerStatus extends State
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
