<?php

namespace App\Data\Media;

use App\Models\Media;
use Spatie\LaravelData\Data;

/**
 * Returned after an upload. Forms submit `id`; poll `scan_status` until clean.
 *
 * Same shape as MediaData on purpose — the picker runs a fresh upload and a
 * library pick through one code path, so a mismatch renders no preview.
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
