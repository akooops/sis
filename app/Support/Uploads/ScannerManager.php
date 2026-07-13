<?php

namespace App\Support\Uploads;

use App\Contracts\Uploads\MalwareScanner;

class ScannerManager
{
    /**
     * Resolve the malware scanner configured in config('media-library.scanner').
     */
    public static function make(): MalwareScanner
    {
        return match (config('media-library.scanner', 'null')) {
            'clamav' => new ClamAvScanner(),
            default => new NullScanner(),
        };
    }
}
