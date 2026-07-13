<?php

namespace App\States\Media;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Malware-scan state for a media row. A freshly uploaded file starts Pending
 * and the ScanUpload job transitions it to Clean or Infected.
 */
abstract class MediaScanState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Pending::class)
            ->allowTransition(Pending::class, Clean::class)
            ->allowTransition(Pending::class, Infected::class);
    }
}
