<?php

namespace App\Services\Uploads;

use App\Data\Media\StoreMediaData;
use App\Data\Media\UploadData;
use App\Enums\MorphType;
use App\Jobs\ScanUpload;
use App\Models\Media;
use App\States\Media\Pending;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The one place files enter, move between owners and leave the system.
 *
 * Storage is flat: every file sits in the same folder under a generated ULID
 * name, and nothing about the owner or the collection is encoded in the path.
 * That is what makes attach/detach pure database writes — no file ever moves
 * except once, out of quarantine, when the scan clears it (App\Jobs\ScanUpload).
 */
class UploadService
{
    /** Collection that holds not-yet-attached uploads. */
    public const TEMP_COLLECTION = 'temp';

    /**
     * Store an uploaded file as an unattached (owner-less) media row on the
     * quarantine disk, queue it for scanning, and return a reference. Uploads
     * are not user-scoped — any authenticated caller may reference the id.
     */
    public static function store(StoreMediaData $data): UploadData
    {
        $disk = config('uploads.quarantine_disk', 'quarantine');

        $media = new Media;
        $media->model_type = null;
        $media->model_id = null;
        $media->collection_name = self::TEMP_COLLECTION;
        $media->name = $data->file->getClientOriginalName();
        $media->file_name = static::generateFileName($data->file->getClientOriginalName());
        $media->mime_type = $data->file->getMimeType();
        $media->disk = $disk;
        $media->size = $data->file->getSize();
        $media->custom_properties = ['type' => $data->type];
        $media->state = Pending::class;
        $media->save();

        Storage::disk($disk)->putFileAs(Media::folder(), $data->file, $media->file_name);

        ScanUpload::dispatch($media);

        return UploadData::from($media);
    }

    /**
     * Attach a scanned upload to a model's media collection.
     *  - Free upload (model_id null): re-own the existing row (no copy).
     *  - Already attached elsewhere: copy it onto the model.
     *
     * For a single-file collection (e.g. avatar) the existing item is first
     * freed back into the reusable pool. Multi-file collections keep all their
     * items — removing one is done explicitly via detach().
     */
    public static function attach(string $mediaId, Model $model, string $collection): void
    {
        $media = Media::query()->whereKey($mediaId)->firstOrFail();

        if (in_array($collection, $model->singleFileCollections(), true)) {
            static::freeCollection($model, $collection);
        }

        if ($media->model_id === null) {
            $media->model()->associate($model);
            $media->collection_name = $collection;
            $media->save();

            static::log($media, 'attached', $model, $collection);

            return;
        }

        static::log(static::copy($media, $model, $collection), 'attached', $model, $collection);
    }

    /**
     * Detach a single media, returning it to the free (reusable) pool. The file
     * is never deleted here — the uploads:prune command removes stale free media.
     */
    public static function detach(Media $media): void
    {
        $owner = $media->model;
        $collection = $media->collection_name;

        $media->forceFill([
            'model_type' => null,
            'model_id' => null,
            'collection_name' => self::TEMP_COLLECTION,
        ])->save();

        static::log($media, 'detached', $owner, $collection);
    }

    /** Delete a media row and its file. */
    public static function delete(Media $media): void
    {
        $media->deleteFile();
        $media->delete();
    }

    /** Free all of a model's media (used when the owner is force-deleted). */
    public static function freeModel(Model $model): void
    {
        Media::query()
            ->where('model_type', $model->getMorphClass())
            ->where('model_id', $model->getKey())
            ->update([
                'model_type' => null,
                'model_id' => null,
                'collection_name' => self::TEMP_COLLECTION,
            ]);
    }

    /**
     * Duplicate a media that is already owned by someone else, so the two models
     * never share a file — deleting one must not blank the other.
     */
    protected static function copy(Media $source, Model $model, string $collection): Media
    {
        $copy = new Media;
        $copy->model_type = $model->getMorphClass();
        $copy->model_id = $model->getKey();
        $copy->collection_name = $collection;
        $copy->name = $source->name;
        $copy->file_name = static::generateFileName($source->file_name);
        $copy->mime_type = $source->mime_type;
        $copy->disk = $source->disk;
        $copy->size = $source->size;
        $copy->custom_properties = $source->custom_properties;
        $copy->state = $source->state::class;
        $copy->save();

        Storage::disk($source->disk)->copy($source->path, $copy->path);

        return $copy;
    }

    /** Free every media in one of a model's collections. */
    protected static function freeCollection(Model $model, string $collection): void
    {
        Media::query()
            ->where('model_type', $model->getMorphClass())
            ->where('model_id', $model->getKey())
            ->where('collection_name', $collection)
            ->update([
                'model_type' => null,
                'model_id' => null,
                'collection_name' => self::TEMP_COLLECTION,
            ]);
    }

    /**
     * Record a change of owner against the file itself. The MediaObserver logs
     * creates and deletes; attaching and detaching are not visible in a column
     * diff worth reading, so they get their own events.
     */
    protected static function log(Media $media, string $event, ?Model $owner, string $collection): void
    {
        activity('media')
            ->performedOn($media)
            ->event($event)
            ->withProperties([
                'model_type' => MorphType::aliasFor($owner?->getMorphClass()),
                'model_id' => $owner?->getKey(),
                'collection' => $collection,
                'meta' => ['name' => $media->name, 'collection' => $collection],
            ])
            ->log($event);
    }

    /**
     * All files share one folder, so the stored name must be globally unique —
     * two people uploading "invoice.pdf" must not collide. Only the extension
     * survives from the original; the real name lives on in media.name.
     */
    protected static function generateFileName(string $originalName): string
    {
        $extension = Str::lower(pathinfo($originalName, PATHINFO_EXTENSION));
        $base = (string) Str::ulid();

        return $extension ? "{$base}.{$extension}" : $base;
    }
}
