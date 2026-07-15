<?php

namespace App\Services\Uploads;

use App\Data\Upload\StoreUploadData;
use App\Data\Upload\UploadData;
use App\Jobs\ScanUpload;
use App\Models\Media;
use App\States\Media\Pending;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Filesystem;

class UploadService
{
    /** Collection that holds not-yet-attached uploads. */
    public const TEMP_COLLECTION = 'temp';

    /**
     * Store an uploaded file as an unattached (owner-less) media row on the
     * quarantine disk, queue it for scanning, and return a reference. Uploads
     * are not user-scoped — any authenticated caller may reference the id.
     */
    public static function store(StoreUploadData $data): UploadData
    {
        $disk = config('media-library.quarantine_disk', 'local');

        $media = new Media();
        $media->model_type = null;
        $media->model_id = null;
        $media->collection_name = self::TEMP_COLLECTION;
        $media->name = pathinfo($data->file->getClientOriginalName(), PATHINFO_FILENAME);
        $media->file_name = static::sanitizeFileName($data->file->getClientOriginalName());
        $media->mime_type = $data->file->getMimeType();
        $media->disk = $disk;
        $media->conversions_disk = $disk;
        $media->size = $data->file->getSize();
        $media->manipulations = [];
        $media->custom_properties = ['type' => $data->type];
        $media->generated_conversions = [];
        $media->responsive_images = [];
        $media->uuid = (string) Str::uuid();
        $media->state = Pending::class;
        $media->save();

        // Let Spatie place the file at its own computed path (id-based, honours
        // any configured prefix/path generator) instead of hardcoding a layout.
        // The target filename MUST be $media->file_name: copyToMediaLibrary would
        // otherwise name it after the source path's basename (PHP's "phpXXXX.tmp"
        // upload temp name), so getPathRelativeToRoot() — "{id}/{file_name}" —
        // would point at a file that doesn't exist and every later readStream()
        // would return null.
        app(Filesystem::class)->copyToMediaLibrary($data->file->getRealPath(), $media, null, $media->file_name);

        ScanUpload::dispatch($media);

        return UploadData::from($media);
    }

    /**
     * Attach a scanned upload to a model's media collection.
     *  - Free upload (model_id null): re-own the existing row (no copy).
     *  - Already attached elsewhere: copy it onto the model.
     *
     * For a single-file collection (e.g. avatar, thumbnail) the existing item is
     * first freed back into the reusable pool. Multi-file collections keep all
     * their items — removing one is done explicitly via detach().
     */
    public static function attach(string $mediaId, HasMedia $model, string $collection): void
    {
        $media = Media::query()->whereKey($mediaId)->firstOrFail();

        if ($model->getMediaCollection($collection)?->singleFile) {
            static::freeCollection($model, $collection);
        }

        if ($media->model_id === null) {
            $media->model()->associate($model);
            $media->collection_name = $collection;
            $media->save();

            return;
        }

        $media->copy($model, $collection);
    }

    /**
     * Detach a single media, returning it to the free (reusable) pool. The file
     * is never deleted here — the uploads:prune command removes stale free media.
     */
    public static function detach(Media $media): void
    {
        $media->forceFill([
            'model_type' => null,
            'model_id' => null,
            'collection_name' => self::TEMP_COLLECTION,
        ])->save();
    }

    /** Free every media in one of a model's collections. */
    protected static function freeCollection(HasMedia $model, string $collection): void
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

    /** Free all of a model's media (used when the owner is force-deleted). */
    public static function freeModel(HasMedia $model): void
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

    protected static function sanitizeFileName(string $name): string
    {
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $base = Str::slug(pathinfo($name, PATHINFO_FILENAME)) ?: 'file';

        return $extension ? "{$base}.{$extension}" : $base;
    }
}
