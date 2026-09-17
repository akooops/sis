<?php

namespace App\Services\Legacy;

use App\Models\Media;
use App\Services\Uploads\UploadService;
use App\States\Media\Clean;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * The old app's `files` table and its bytes, brought onto this app's Media.
 *
 * ── THE ONE DECISION THAT MATTERS: FILE NAMES ARE PRESERVED ──────────────────
 *
 * Both apps store uploads flat in one folder on the public disk — the old one as
 * `uploads/{uuid}.{ext}`, this one as `uploads/{ulid}.{ext}` — and both serve
 * that folder at `/storage/uploads/`. So a legacy file copied across UNDER ITS
 * OWN NAME keeps the exact URL it had before.
 *
 * That is what makes migrating a decade of rich-text content tractable. Editor
 * images were never attached to anything in either app (they are "free media"
 * here by design); they live only as `<img src="/storage/uploads/....jpg">`
 * inside a translated `content` column. Preserving names means those bodies come
 * across byte-for-byte and every inline image still resolves — no HTML parsing,
 * no rewriting, nothing to get wrong on a page nobody thought to check.
 *
 * It costs nothing: `media.file_name` is unique across the table, and a legacy
 * UUID can never collide with a ULID this app generates.
 *
 * ── ATTACHMENT IS THE IMPORTERS' JOB, NOT THIS CLASS'S ───────────────────────
 *
 * A legacy `files` row carries its owner as a LEGACY id, which means nothing here
 * yet. So every file is imported as a FREE media row — owner-less, which is a
 * first-class state in this app and is how it shows up in the Media library —
 * and each content importer then calls attach() for the one file it knows about.
 * A file nobody claims stays free, which is correct for an editor image and
 * harmless for anything else: nothing prunes public media.
 */
class LegacyFiles
{
    /**
     * LEGACY OWNER => THE UPLOAD TARGET ITS FILES BELONG ON.
     *
     * A PRIVACY FIX, NOT A PORT. The old app had one disk, so a CV attached to a
     * job application sat in the same world-readable folder as the home-page
     * hero, reachable by anyone who had or guessed the URL. This app puts form
     * attachments on a disk that is not web-readable at all and serves them only
     * through a signed, permission-gated route — which is why Media::url()
     * returns null for them rather than a plausible link that 404s.
     *
     * Carrying those files onto the public disk "because that is where they were"
     * would import the old app's weakest decision along with its data. Anything
     * not listed keeps the public target, which is right for content images.
     */
    protected const TARGETS = [
        'App\Models\JobApplication' => 'private:forms',
    ];

    /** The default target for a file with no listed owner. */
    protected string $disk;

    protected string $folder;

    /** Files named in the legacy DB that were not on disk. */
    protected array $missing = [];

    protected int $copied = 0;

    protected int $skipped = 0;

    /** Media rows an earlier run already wrote — the re-run path. */
    protected int $reused = 0;

    public function __construct(
        protected LegacyDatabase $db,
        protected LegacyIdMap $map,
        /** Absolute path to the handed-over upload directory, or null. */
        protected ?string $root,
        /** Write the media row even when the file itself is absent. */
        protected bool $allowMissing = false,
    ) {
        ['disk' => $this->disk, 'folder' => $this->folder] = UploadService::resolveTarget('public');
    }

    /**
     * Resolve the directory the old uploads were handed over in.
     *
     * Accepts either the old `storage/app/public` (which CONTAINS `uploads/`) or
     * the `uploads` folder itself, because both are things a person reasonably
     * hands you when asked for "the files", and guessing wrong means an import
     * that reports every single file missing.
     */
    public static function resolveRoot(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $path = rtrim(str_replace('\\', '/', trim($path)), '/');

        if (! is_dir($path)) {
            throw new RuntimeException("The legacy files directory [{$path}] does not exist.");
        }

        // Handed the disk root: step into the folder the old FileService wrote to.
        if (is_dir($path.'/uploads')) {
            return $path.'/uploads';
        }

        return $path;
    }

    /**
     * Import one legacy file as a free media row and return it.
     *
     * Idempotent twice over: the id map means a second run finds the row it wrote
     * the first time, and the copy is skipped when a file of the same size is
     * already on the target disk — so re-running over 4,000 images is a table
     * scan rather than 4,000 file copies.
     */
    public function import(object $row): ?Media
    {
        $existing = $this->map->find('files', (int) $row->id);

        if ($existing && $media = Media::find($existing)) {
            $this->ensureBytes($row, $media);
            $this->reused++;

            return $media;
        }

        $fileName = $this->fileName($row);
        $target = static::TARGETS[$row->model_type ?? ''] ?? 'public';
        ['disk' => $disk, 'folder' => $folder] = UploadService::resolveTarget($target);

        /*
         * Match an already-imported file by NAME as well as by the map. The map
         * can be missing while the file is present — someone ran the import,
         * dropped legacy_imports, and ran it again — and file_name is unique, so
         * inserting again would throw rather than reconcile.
         */
        $media = Media::query()->where('file_name', $fileName)->first() ?? new Media;

        if (! $this->copy($row, $fileName, $disk, $folder) && ! $this->allowMissing) {
            return null;
        }

        $media->fill([
            // Free: no owner until a content importer claims it.
            'model_type' => $media->model_type,
            'model_id' => $media->model_id,
            'collection_name' => $media->collection_name ?: UploadService::TEMP_COLLECTION,
            // The display name is the filename the person originally uploaded.
            'name' => $row->original_name ?: $fileName,
            'file_name' => $fileName,
            'mime_type' => $row->type ?: null,
            'size' => (int) ($row->size ?? 0),
            'disk' => $disk,
            'folder' => $folder,
            'custom_properties' => ['target' => $target, 'legacy_file_id' => (int) $row->id],
            /*
             * CLEAN, not pending. These files were served publicly by the old app
             * for years; putting them through ScanUpload would move them into
             * quarantine and leave every URL in every imported page broken until a
             * queue worker caught up.
             */
            'state' => Clean::class,
        ]);

        $media->created_at = $row->created_at ?? now();
        $media->save();

        $this->map->put('files', (int) $row->id, $media);

        return $media;
    }

    /**
     * Give an imported file to a model's collection.
     *
     * Mirrors UploadService::attach without going through it, because that reads
     * a media id off a request and re-resolves the model; here both are already
     * in hand, and a single-file collection must be freed first or a re-run would
     * leave two thumbnails on one article and the wrong one would win on order.
     */
    public function attach(int|string|null $legacyFileId, Model $model, string $collection, ?int $order = null): ?Media
    {
        if (! is_numeric($legacyFileId)) {
            return null;
        }

        $id = $this->map->find('files', (int) $legacyFileId);
        $media = $id ? Media::find($id) : null;

        if (! $media) {
            return null;
        }

        // Already where it belongs: nothing to write, so no observer row either.
        $owned = $media->model_type === $model->getMorphClass()
            && (string) $media->model_id === (string) $model->getKey()
            && $media->collection_name === $collection;

        if (! $owned && in_array($collection, $model->singleFileCollections(), true)) {
            Media::query()
                ->where('model_type', $model->getMorphClass())
                ->where('model_id', $model->getKey())
                ->where('collection_name', $collection)
                ->whereKeyNot($media->getKey())
                ->update(['model_type' => null, 'model_id' => null, 'collection_name' => UploadService::TEMP_COLLECTION]);
        }

        $media->model_type = $model->getMorphClass();
        $media->model_id = $model->getKey();
        $media->collection_name = $collection;
        $media->order_column = $order;
        $media->save();

        return $media;
    }

    /**
     * The name to store the file under.
     *
     * `files.name` is what the old FileService generated and what every inline
     * `<img>` in the old content points at, so it is the name that must survive.
     * `path` is the fallback for a row written before that column settled.
     */
    protected function fileName(object $row): string
    {
        $name = trim((string) ($row->name ?? ''));

        return $name !== '' ? $name : basename((string) $row->path);
    }

    /** Copy the bytes across, reporting rather than throwing when they are gone. */
    protected function copy(object $row, string $fileName, string $diskName, string $folder): bool
    {
        $source = $this->source($row);

        if ($source === null) {
            $this->missing[] = (string) ($row->path ?? $fileName);

            return false;
        }

        $target = trim($folder, '/').'/'.$fileName;
        $disk = Storage::disk($diskName);

        if ($disk->exists($target) && $disk->size($target) === filesize($source)) {
            $this->skipped++;

            return true;
        }

        $stream = fopen($source, 'rb');

        if ($stream === false) {
            $this->missing[] = $source;

            return false;
        }

        $disk->writeStream($target, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        $this->copied++;

        return true;
    }

    /**
     * Re-copy for a row that was mapped on an earlier run whose file never
     * arrived — the case where the database was imported first and the files
     * were rsynced afterwards.
     */
    protected function ensureBytes(object $row, Media $media): void
    {
        if (Storage::disk($media->disk)->exists($media->path)) {
            return;
        }

        $this->copy($row, $media->file_name, $media->disk, (string) ($media->getAttributes()['folder'] ?? $this->folder));
    }

    /** The file on disk, trying the recorded path and then a flat lookup. */
    protected function source(object $row): ?string
    {
        if ($this->root === null) {
            return null;
        }

        $candidates = [
            // `files.path` is `uploads/<name>`, and $root already IS uploads.
            $this->root.'/'.basename((string) $row->path),
            $this->root.'/'.$this->fileName($row),
            // A handover that kept the disk layout under the folder we resolved.
            $this->root.'/'.ltrim((string) $row->path, '/'),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /** @return array{copied: int, skipped: int, reused: int, missing: array<int, string>} */
    public function stats(): array
    {
        return [
            'copied' => $this->copied,
            'skipped' => $this->skipped,
            'reused' => $this->reused,
            'missing' => $this->missing,
        ];
    }

    /** Whether this legacy file was already imported by an earlier run. */
    public function alreadyImported(object $row): bool
    {
        return $this->map->find('files', (int) $row->id) !== null;
    }

    public function hasRoot(): bool
    {
        return $this->root !== null;
    }
}
