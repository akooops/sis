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
 * The one place files enter, change owner and leave.
 *
 * Storage is flat: every file is a generated ULID name in one folder, and the
 * owner is in the DB only. That is what makes attach/detach pure database
 * writes — a file moves exactly once, out of quarantine (App\Jobs\ScanUpload).
 */
class UploadService
{
    /** Collection that holds not-yet-attached uploads. */
    public const TEMP_COLLECTION = 'temp';

    /**
     * Store a file as an owner-less media row on quarantine and queue the scan.
     * Not user-scoped: any authenticated caller may reference the returned id.
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

        // On a sync queue the scan already ran and moved the file, so re-read: the
        // in-memory row still says pending/quarantine and yields no url.
        return UploadData::from($media->fresh() ?? $media);
    }

    /**
     * Attach a scanned upload to a model's collection.
     *  - Free upload (model_id null): re-own the row, no copy.
     *  - Already attached elsewhere: copy it onto the model.
     *
     * A single-file collection frees its existing item first; multi-file keeps all
     * of them (remove one via detach()).
     *
     * Returns the media the model ENDS UP owning, which on the copy path is a new
     * row. Callers recording what was attached must use the return value.
     */
    public static function attach(string $mediaId, Model $model, string $collection): Media
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

            return $media;
        }

        $copy = static::copy($media, $model, $collection);

        static::log($copy, 'attached', $model, $collection);

        return $copy;
    }

    /**
     * Make a collection hold exactly these media: attach what is new, keep what is
     * there, detach the rest.
     *
     * Every id goes through attach(), so picking a file that already belongs to
     * something COPIES it. Skipping owned media (as this once did) made a library
     * pick silently vanish on save, and sharing a row would let one delete blank
     * the other.
     *
     * A copy has a new id, so the collection ends up holding an id the caller did
     * not send. That converges: the response carries the new ids and the form
     * reseeds from it. One copy per pick, not one per save.
     *
     * Multi-file only: on a single-file collection attach() frees the collection,
     * so two incoming ids would fight over it.
     */
    public static function sync(array $mediaIds, Model $model, string $collection): void
    {
        $mediaIds = array_values(array_unique(array_filter($mediaIds)));

        // Raw query, NOT getMedia(): that filters to Clean, so a still-scanning upload
        // would be invisible and neither detached nor recognised as held.
        $current = Media::query()
            ->where('model_type', $model->getMorphClass())
            ->where('model_id', $model->getKey())
            ->where('collection_name', $collection)
            ->get();

        foreach ($current as $media) {
            if (! in_array($media->getKey(), $mediaIds, true)) {
                static::detach($media);
            }
        }

        // Submitted order IS display order, and a copy's id is only known after attach().
        $ordered = [];

        foreach ($mediaIds as $mediaId) {
            $held = $current->firstWhere('id', $mediaId);

            $ordered[] = $held ?: static::attach($mediaId, $model, $collection);
        }

        // getMedia() sorts by order_column but nothing wrote it, so a gallery came back
        // in creation order. Builder updates: reordering writes no audit rows.
        foreach ($ordered as $position => $media) {
            Media::query()->whereKey($media->getKey())->update(['order_column' => $position]);
        }
    }

    /**
     * Return a media to the free pool. The file is never deleted here, and nothing
     * deletes it later either — DELETE media/{media} is the only exit.
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

    /** Free all of a model's media, for when the owner is deleted. */
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

    /** Duplicate an owned media so two models never share a file. */
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
     * Record a change of owner against the file. MediaObserver logs creates and
     * deletes; attach/detach are not a readable column diff, so they get events.
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
     * One folder for everything, so the stored name must be globally unique — two
     * "invoice.pdf" must not collide. Only the extension survives; media.name keeps
     * the real one.
     */
    protected static function generateFileName(string $originalName): string
    {
        $extension = Str::lower(pathinfo($originalName, PATHINFO_EXTENSION));
        $base = (string) Str::ulid();

        return $extension ? "{$base}.{$extension}" : $base;
    }
}
