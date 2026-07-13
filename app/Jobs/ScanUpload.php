<?php

namespace App\Jobs;

use App\Models\Media;
use App\States\Media\Clean;
use App\States\Media\Infected;
use App\Support\Uploads\ScannerManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ScanUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Media $media,
    ) {}

    public function handle(): void
    {
        $quarantineDisk = $this->media->disk;
        $relativePath = $this->media->getPathRelativeToRoot(); // Spatie-derived, id-based
        $absolutePath = Storage::disk($quarantineDisk)->path($relativePath);

        // Infected -> remove the file directory and flag it.
        if (! ScannerManager::make()->isClean($absolutePath)) {
            Storage::disk($quarantineDisk)->deleteDirectory(dirname($relativePath));
            $this->media->state->transitionTo(Infected::class);

            return;
        }

        // Clean -> move out of quarantine onto the public/default disk (same
        // relative path, so getUrl() resolves once attached).
        $targetDisk = config('media-library.disk_name', 'public');

        if ($targetDisk !== $quarantineDisk) {
            Storage::disk($targetDisk)->writeStream(
                $relativePath,
                Storage::disk($quarantineDisk)->readStream($relativePath),
            );
            Storage::disk($quarantineDisk)->deleteDirectory(dirname($relativePath));

            $this->media->disk = $targetDisk;
            $this->media->conversions_disk = $targetDisk;
            $this->media->save();
        }

        $this->media->state->transitionTo(Clean::class);
    }
}
