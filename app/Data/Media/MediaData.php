<?php

namespace App\Data\Media;

use App\Models\Media;
use Spatie\LaravelData\Data;

/**
 * Output DTO for the media library index (browse/reuse existing media). Unlike
 * mediaData it also exposes a `url` (null until the file passes the scan and
 * reaches the public disk) and whether the media is currently attached.
 */
class MediaData extends Data
{
    public function __construct(
        public string $id,
        public string $type,
        public string $name,
        public string $file_name,
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
        return new self(
            id: $media->id,
            type: (string) $media->getCustomProperty('type'),
            name: $media->name, // the original filename, for display
            file_name: $media->file_name, // the generated ULID name on disk
            mime: $media->mime_type,
            size: (int) $media->size,
            scan_status: $media->state?->getMorphClass() ?? 'pending',
            url: $media->url,
            collection: (string) $media->collection_name,
            attached: $media->model_id !== null,
            created_at: $media->created_at?->toIso8601String(),
            updated_at: $media->updated_at?->toIso8601String(),
        );
    }
}
