<?php

namespace App\Console\Commands;

use App\Services\Analytics\GeoResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use MaxMind\Db\Reader;
use Throwable;

/**
 * Downloads the IP-to-country database the analytics module and the forms
 * module's country blocking both read.
 *
 * NOT BUNDLED WITH THE REPOSITORY. It is ~40 MB of binary that changes monthly,
 * and its licence would have to travel with it. Everything fails open to null
 * until this has run, so a fresh deployment is never blocked on it - the
 * Countries card is simply empty and a country block simply does not fire.
 *
 * TWO SOURCES, ONE FORMAT. DB-IP Lite and MaxMind GeoLite2 both ship the same
 * .mmdb binary, so the vendor is a config value and this command is the only
 * place that knows the difference between their download URLs.
 */
class UpdateGeoipDatabase extends Command
{
    protected $signature = 'analytics:geoip-update {--force : Download even when the current file is recent}';

    protected $description = 'Download or refresh the IP-to-country database';

    public function handle(): int
    {
        $destination = (string) config('analytics.geo.database');

        if ($destination === '') {
            $this->error('analytics.geo.database is not configured.');

            return self::FAILURE;
        }

        if (! $this->option('force') && $this->isFresh($destination)) {
            $this->info('The database is less than 25 days old. Use --force to download anyway.');

            return self::SUCCESS;
        }

        $url = $this->url();

        if ($url === null) {
            return self::FAILURE;
        }

        @mkdir(dirname($destination), 0775, true);

        $archive = $destination.'.download.gz';
        $candidate = $destination.'.download';

        try {
            $this->line("Downloading {$url}");

            $response = Http::timeout(300)->sink($archive)->get($url);

            if (! $response->successful()) {
                $this->error("Download failed with HTTP {$response->status()}.");

                return self::FAILURE;
            }

            $this->line('Decompressing...');
            $this->decompress($archive, $candidate);

            /*
             * SANITY CHECK BEFORE THE SWAP, and it is not optional.
             *
             * A truncated or HTML-error-page download otherwise swaps in cleanly
             * and every lookup afterwards returns null with no error anywhere -
             * indistinguishable from "nobody visited from abroad". This is also
             * the ONLY thing that ever exercises the reader on an installation
             * sitting behind Cloudflare, where the header always wins.
             */
            $this->verify($candidate);

            // Atomic, so a live FPM worker can never read a half-written file -
            // the same temp-file-then-rename recipe TranslationService::put() uses.
            if (! @rename($candidate, $destination)) {
                $this->error('Could not move the database into place.');

                return self::FAILURE;
            }

            GeoResolver::flush();

            $this->info('Country database updated: '.$destination);
            $this->line($this->describe($destination));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            // Never leave a partial file behind for the next run to trip over.
            @unlink($archive);
            @unlink($candidate);
        }
    }

    /** Whether the installed file is recent enough to leave alone. */
    protected function isFresh(string $path): bool
    {
        return is_file($path) && filemtime($path) > now()->subDays(25)->getTimestamp();
    }

    /**
     * Where to fetch from, per the configured vendor.
     *
     * DB-IP publishes a MONTH-STAMPED file on the 1st, and it can 404 for a few
     * hours - so a missing current month falls back to the previous one rather
     * than failing a scheduled run over a publishing race.
     */
    protected function url(): ?string
    {
        $source = (string) config('analytics.geo.source', 'dbip');

        if ($source === 'maxmind') {
            $key = (string) config('analytics.geo.maxmind.license_key');

            if ($key === '') {
                // Said plainly, because without it MaxMind answers with an HTML
                // error page that would download happily and verify as garbage.
                $this->error('MAXMIND_LICENSE_KEY is not set. Set it, or use ANALYTICS_GEO_SOURCE=dbip which needs no account.');

                return null;
            }

            $edition = (string) config('analytics.geo.maxmind.edition', 'GeoLite2-Country');

            return 'https://download.maxmind.com/app/geoip_download'
                ."?edition_id={$edition}&license_key={$key}&suffix=tar.gz";
        }

        foreach ([Carbon::now(), Carbon::now()->subMonth()] as $month) {
            $url = 'https://download.db-ip.com/free/dbip-country-lite-'.$month->format('Y-m').'.mmdb.gz';

            if (Http::timeout(30)->head($url)->successful()) {
                return $url;
            }

            $this->line('Not published yet: '.$month->format('Y-m'));
        }

        $this->error('No DB-IP country database is available for this month or last.');

        return null;
    }

    /**
     * gunzip, in a loop rather than into memory.
     *
     * ext-zlib only. The .tar.gz MaxMind ships would need PharData and knowledge
     * of the dated directory inside it - which is the other reason dbip is the
     * default source.
     */
    protected function decompress(string $archive, string $target): void
    {
        $in = @gzopen($archive, 'rb');

        if ($in === false) {
            throw new \RuntimeException('The downloaded file could not be opened.');
        }

        $out = @fopen($target, 'wb');

        if ($out === false) {
            gzclose($in);

            throw new \RuntimeException('Could not write the decompressed database.');
        }

        try {
            while (! gzeof($in)) {
                $chunk = gzread($in, 262144);

                if ($chunk === false) {
                    throw new \RuntimeException('The download is corrupt.');
                }

                fwrite($out, $chunk);
            }
        } finally {
            gzclose($in);
            fclose($out);
        }
    }

    /** Open the candidate and look up a known address before trusting it. */
    protected function verify(string $path): void
    {
        if (! class_exists(Reader::class)) {
            throw new \RuntimeException('maxmind-db/reader is not installed. Run: composer require maxmind-db/reader');
        }

        $reader = new Reader($path);
        $record = $reader->get('8.8.8.8');
        $code = $record['country']['iso_code'] ?? null;

        if ($code !== 'US') {
            throw new \RuntimeException('The downloaded database did not resolve a known address; refusing to install it.');
        }
    }

    /** What vintage is now live, for the log. */
    protected function describe(string $path): string
    {
        try {
            $metadata = (new Reader($path))->metadata();

            return sprintf(
                'Build %s, %s nodes, %d MB.',
                Carbon::createFromTimestamp($metadata->buildEpoch)->toDateString(),
                number_format($metadata->nodeCount),
                (int) round(filesize($path) / 1048576),
            );
        } catch (Throwable) {
            return '';
        }
    }
}
