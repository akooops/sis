<?php

namespace App\Services\Uploads;

use App\Contracts\Uploads\MalwareScanner;

class ScannerManager
{
    /**
     * Resolve the malware scanner configured in config('uploads.scanner').
     */
    public static function make(): MalwareScanner
    {
        return match (config('uploads.scanner', 'null')) {
            'clamav' => new ClamAvScanner,
            default => new NullScanner,
        };
    }
}
