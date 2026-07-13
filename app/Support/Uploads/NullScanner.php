<?php

namespace App\Support\Uploads;

use App\Contracts\Uploads\MalwareScanner;

/**
 * Placeholder scanner that treats every file as clean.
 * REPLACE with a real scanner (e.g. ClamAvScanner) before production.
 */
class NullScanner implements MalwareScanner
{
    public function isClean(string $absolutePath): bool
    {
        return true;
    }
}
