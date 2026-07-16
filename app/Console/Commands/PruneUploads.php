<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\Uploads\UploadService;
use Illuminate\Console\Command;

class PruneUploads extends Command
{
    protected $signature = 'uploads:prune';

    protected $description = 'Delete unattached (temporary) media older than the configured age.';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) config('uploads.max_orphaned_files_age', 30));
        $count = 0;

        Media::query()
            ->whereNull('model_id')
            ->where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(200, function ($items) use (&$count) {
                foreach ($items as $media) {
                    UploadService::delete($media);
                    $count++;
                }
            });

        $this->info("Pruned {$count} orphaned upload(s).");

        return self::SUCCESS;
    }
}
