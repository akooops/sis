<?php

namespace App\Data\Upload;

use App\Models\Media;
use Spatie\LaravelData\Data;
use Throwable;

/**
 * Output DTO for the media library index (browse/reuse existing media). Unlike
 * UploadData it also exposes a `url` (best-effort) and whether the media is
 * currently attached to a model.
 */
class MediaData extends Data
{
    public function __construct(
        public string $id,
        public string $type,
        public string $name,
        public ?string $mime,
        public int $size,
        public string $scan_status,
        public ?string $url,
        public string $collection,
        public bool $attached,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Media $media): self
    {
        $url = null;
        try {
            $url = $media->getUrl();
        } catch (Throwable) {
            // Temp/quarantine media may not have a public URL yet.
        }

        return new self(
            id: $media->id,
            type: (string) $media->getCustomProperty('type'),
            name: $media->file_name,
            mime: $media->mime_type,
            size: (int) $media->size,
            scan_status: $media->state?->getMorphClass() ?? 'pending',
            url: $url,
            collection: (string) $media->collection_name,
            attached: $media->model_id !== null,
            created_at: $media->created_at?->toIso8601String(),
            updated_at: $media->updated_at?->toIso8601String(),
        );
    }
}
