<?php

namespace App\States\Media;

/**
 * The scanner could not reach a verdict (e.g. clamd was unreachable) after all
 * retries. The file stays in quarantine and is never promoted — Failed is not
 * Clean. It can be retried by transitioning back to Pending and re-dispatching
 * ScanUpload; otherwise the daily model:prune sweeps it up like any other free media.
 */
class Failed extends MediaScanState
{
    public static string $name = 'failed';
}
