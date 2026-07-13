<?php

namespace App\Data\Upload;

use App\Models\Media;
use Spatie\LaravelData\Data;

/**
 * Output DTO returned after an upload. The form later submits `id` as the
 * reference; poll `scan_status` until it is "clean".
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
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Media $media): self
    {
        return new self(
            id: $media->id,
            type: (string) $media->getCustomProperty('type'),
            name: $media->file_name,
            mime: $media->mime_type,
            size: (int) $media->size,
            scan_status: $media->state?->getMorphClass() ?? 'pending',
            created_at: $media->created_at?->toIso8601String(),
            updated_at: $media->updated_at?->toIso8601String(),
        );
    }
}
