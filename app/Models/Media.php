<?php

namespace App\Models;

use App\States\Media\Clean;
use App\States\Media\MediaScanState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Spatie\ModelStates\HasStates;
use Throwable;

/**
 * A stored file. Owner-less until attached: `model_type`/`model_id` are null
 * while the upload sits in the reusable pool, and a file belongs to at most one
 * model at a time (App\Services\Uploads\UploadService attaches, detaches and
 * copies).
 *
 * Every file lives in one flat folder, so `file_name` is a generated ULID and is
 * unique across the table — the owner and collection are recorded here, never in
 * the path. `name` keeps the original filename for display.
 */
class Media extends Model
{
    use HasStates, HasUlids, Prunable;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $casts = [
        'custom_properties' => 'array',
        'state' => MediaScanState::class,
        'size' => 'integer',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /** Path on `disk`, relative to the disk root. */
    public function getPathAttribute(): string
    {
        return static::folder().$this->file_name;
    }

    /**
     * Public URL, or null when there isn't one: a file only reaches the public
     * disk after it passes the scan, and the quarantine disk serves nothing.
     */
    public function getUrlAttribute(): ?string
    {
        if (! $this->state instanceof Clean) {
            return null;
        }

        try {
            return Storage::disk($this->disk)->url($this->path);
        } catch (Throwable) {
            return null;
        }
    }

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    public function getCustomProperty(string $key, mixed $default = null): mixed
    {
        return data_get($this->custom_properties, $key, $default);
    }

    /**
     * What `model:prune` deletes (scheduled daily in App\Console\Kernel): an
     * upload nobody ever attached, past the configured age. Replaces the old
     * uploads:prune command — Prunable already chunks, and pruning() below is
     * the per-row hook that takes the file with the row.
     */
    public function prunable(): Builder
    {
        return static::query()
            ->whereNull('model_id')
            ->where('created_at', '<', now()->subDays((int) config('uploads.max_orphaned_files_age', 30)));
    }

    /**
     * Called once per row, before it is deleted — which is exactly why a command
     * is unnecessary: the file goes with its row, and a mass delete could never
     * have done that.
     */
    protected function pruning(): void
    {
        $this->deleteFile();
    }

    /** Delete just this file — never the folder, which is shared by every media. */
    public function deleteFile(): void
    {
        Storage::disk($this->disk)->delete($this->path);
    }

    /** The one folder every file is stored in, with a trailing slash. */
    public static function folder(): string
    {
        $folder = trim((string) config('uploads.folder', 'uploads'), '/');

        return $folder === '' ? '' : $folder.'/';
    }
}
