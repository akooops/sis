<?php

namespace App\Services\Uploads;

use App\Contracts\Uploads\MalwareScanner;
use RuntimeException;

/**
 * Scans files with a running clamd daemon over its INSTREAM protocol
 * (unix socket or TCP). No PHP extension required. Configure via
 * config/uploads.php -> 'clamav'. Set FILE_SCANNER=clamav to enable.
 */
class ClamAvScanner implements MalwareScanner
{
    private const CHUNK = 8192;

    public function isClean(string $absolutePath): bool
    {
        if (! is_readable($absolutePath)) {
            throw new RuntimeException("Cannot read file for scanning: {$absolutePath}");
        }

        $config = (array) config('uploads.clamav');
        $timeout = (int) ($config['timeout'] ?? 30);

        $address = ! empty($config['socket'])
            ? $config['socket']
            : sprintf('tcp://%s:%d', $config['host'] ?? '127.0.0.1', $config['port'] ?? 3310);

        $socket = @stream_socket_client($address, $errno, $errstr, $timeout);

        if ($socket === false) {
            throw new RuntimeException("Unable to connect to clamd at {$address}: {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, $timeout);

        try {
            fwrite($socket, "zINSTREAM\0");

            $handle = fopen($absolutePath, 'rb');
            while (! feof($handle)) {
                $chunk = fread($handle, self::CHUNK);
                $length = strlen($chunk);

                if ($length > 0) {
                    fwrite($socket, pack('N', $length).$chunk);
                }
            }
            fclose($handle);

            // Zero-length chunk terminates the stream.
            fwrite($socket, pack('N', 0));

            $response = trim((string) fgets($socket));
        } finally {
            fclose($socket);
        }

        // "stream: OK" = clean; "stream: <signature> FOUND" = infected.
        if (str_contains($response, 'FOUND')) {
            return false;
        }

        if (str_contains($response, 'OK')) {
            return true;
        }

        throw new RuntimeException("Unexpected clamd response: {$response}");
    }
}
