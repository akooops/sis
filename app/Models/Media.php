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
 * A stored file. Owner-less until attached (model_type/model_id null while it
 * sits in the reusable pool), and owned by at most one model at a time —
 * UploadService attaches, detaches and copies.
 *
 * One flat folder for everything, so `file_name` is a generated ULID unique
 * across the table; the owner is recorded here, never in the path. `name` keeps
 * the original filename for display.
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

    /**
     * Path on `disk`, relative to the disk root.
     *
     * `folder` is null for everything on the public disk — the historic flat
     * layout — and set for a private feature that claimed its own folder in
     * config('uploads.disks'). Either way the layout stays flat inside the
     * folder, so a file never moves when it changes owner.
     */
    public function getPathAttribute(): string
    {
        $folder = trim((string) ($this->folder ?? ''), '/');

        return $folder === ''
            ? static::folder().$this->file_name
            : $folder.'/'.$this->file_name;
    }

    /**
     * A publicly reachable URL, or null.
     *
     * Null until the file passes the scan — the quarantine disk serves nothing.
     *
     * Null for ANY disk other than the configured public one, which is the
     * load-bearing part. Storage::url() on Laravel's `local` disk happily
     * returns `/storage/<path>`, pointing into the PUBLIC symlink where the file
     * is not — so a private form attachment would come back with a plausible
     * link that 404s, and the admin UI would render it as though the file were
     * downloadable. A private file has no URL at all; it is reached through a
     * signed, permission-gated route instead.
     */
    public function getUrlAttribute(): ?string
    {
        if (! $this->state instanceof Clean || ! $this->isPublic()) {
            return null;
        }

        try {
            return Storage::disk($this->disk)->url($this->path);
        } catch (Throwable) {
            return null;
        }
    }

    /** Whether this file lives on the world-readable disk. */
    public function isPublic(): bool
    {
        return $this->disk === config('uploads.disks.public.disk', config('uploads.disk'));
    }

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    public function getCustomProperty(string $key, mixed $default = null): mixed
    {
        return data_get($this->custom_properties, $key, $default);
    }

    /** Delete just this file — never the folder, which is shared by every media. */
    public function deleteFile(): void
    {
        Storage::disk($this->disk)->delete($this->path);
    }

    /**
     * A NARROW carve-out from "media is never pruned".
     *
     * The standing rule exists because editor-inserted images are free media by
     * design: they live in a page's HTML while being owned by nothing, so a
     * blanket sweep would eventually delete a file a live page still renders.
     *
     * A private form upload is different in kind. It is never inserted into any
     * page's HTML, it is only ever reachable through a signed admin route, and
     * an unattached one means somebody started a form and walked away. Without
     * this, every abandoned form with a file leaks a file forever and a script
     * can fill the disk ten at a time.
     *
     * Predicated on disk + folder — indexed columns, added together for exactly
     * this — never on a JSON path, which would scan the whole table nightly.
     * `model_id` null is the load-bearing part: an ATTACHED file belongs to a
     * submission and is never touched.
     */
    public function prunable(): Builder
    {
        $private = config('uploads.disks.private', []);
        $folder = $private['folders']['forms'] ?? null;

        // No configured private form folder means nothing to prune. Match
        // nothing rather than everything.
        if (! is_string($folder) || $folder === '') {
            return static::query()->whereRaw('1 = 0');
        }

        return static::query()
            ->where('disk', $private['disk'] ?? null)
            ->where('folder', trim($folder, '/'))
            ->whereNull('model_id')
            ->where('created_at', '<', now()->subDays((int) config('uploads.prune_private_after_days', 7)));
    }

    /** Prunable deletes the row; the file has to go with it. */
    protected function pruning(): void
    {
        $this->deleteFile();
    }

    /** The one folder every file is stored in, with a trailing slash. */
    public static function folder(): string
    {
        $folder = trim((string) config('uploads.folder', 'uploads'), '/');

        return $folder === '' ? '' : $folder.'/';
    }
}
