<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\Uploads\ScannerManager;
use App\States\Media\Clean;
use App\States\Media\Failed;
use App\States\Media\Infected;
use App\States\Media\Pending;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ScanUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** A scanner outage is usually transient; give clamd a chance to come back. */
    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public Media $media,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(): void
    {
        $quarantineDisk = $this->media->disk;
        $path = $this->media->path;

        // Infected -> delete just this file and flag it.
        if (! ScannerManager::make()->isClean(Storage::disk($quarantineDisk)->path($path))) {
            Storage::disk($quarantineDisk)->delete($path);

            $this->media->state->transitionTo(Infected::class);
            $this->log('scanned-infected');

            return;
        }

        // Clean -> move out of quarantine onto the public disk. The path is the
        // same on both disks, so the url resolves as soon as the state flips.
        $targetDisk = config('uploads.disk', 'public');

        if ($targetDisk !== $quarantineDisk) {
            Storage::disk($targetDisk)->writeStream(
                $path,
                Storage::disk($quarantineDisk)->readStream($path),
            );

            Storage::disk($quarantineDisk)->delete($path);

            $this->media->disk = $targetDisk;
            $this->media->save();
        }

        $this->media->state->transitionTo(Clean::class);
        $this->log('scanned-clean');
    }

    /**
     * Every retry is spent: the scanner never gave a verdict, so the file stays
     * in quarantine as Failed. Without this the row would sit in Pending
     * forever and CleanUpload would keep rejecting it with no explanation.
     */
    public function failed(?Throwable $exception): void
    {
        $media = $this->media->fresh();

        if (! $media?->state instanceof Pending) {
            return;
        }

        $media->state->transitionTo(Failed::class);
        $this->log('scan-failed', ['error' => $exception?->getMessage()]);
    }

    /**
     * The scan runs on the queue, so there is no causer — these read as done by
     * the system, which is exactly what happened.
     *
     * @param  array<string, mixed>  $properties
     */
    protected function log(string $event, array $properties = []): void
    {
        activity('media')
            ->performedOn($this->media)
            ->event($event)
            ->withProperties($properties + ['meta' => ['name' => $this->media->name]])
            ->log($event);
    }
}
