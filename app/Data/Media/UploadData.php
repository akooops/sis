<?php

namespace App\Data\Media;

use App\Models\Media;
use Spatie\LaravelData\Data;

/**
 * Output DTO returned after an upload. The form later submits `id` as the
 * reference; poll `scan_status` until it is "clean".
 *
 * Carries `url` for the same reason MediaData does: the media picker takes a
 * fresh upload and a pick from the library through the same code path, so the
 * two must be the same shape or the preview silently renders nothing. It is
 * null until the file passes the scan and reaches the public disk.
 */
class UploadData extends Data
{
    public function __construct(
        public string $id,
        public string $type,
        public string $name,
        public ?string $mime,
        public int $size,
        public string $scan_status,
        public ?string $url,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Media $media): self
    {
        return new self(
            id: $media->id,
            type: (string) $media->getCustomProperty('type'),
            name: $media->name, // the original filename; file_name is a generated ULID
            mime: $media->mime_type,
            size: (int) $media->size,
            scan_status: $media->state?->getMorphClass() ?? 'pending',
            url: $media->url,
            created_at: $media->created_at?->toIso8601String(),
            updated_at: $media->updated_at?->toIso8601String(),
        );
    }
}
