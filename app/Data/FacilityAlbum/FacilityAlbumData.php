<?php

namespace App\Data\FacilityAlbum;

use App\Data\Album\AlbumData;
use App\Models\FacilityAlbum;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * Output DTO for one attachment of a photo album to a venue.
 *
 * The property is `album` and the relation is `album` too — PivotDrawer sends
 * that name as `include=` and reads the cell out of it, so the two must agree.
 */
class FacilityAlbumData extends Data
{
    public function __construct(
        public string $id,
        public string $facility_id,
        public string $album_id,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|AlbumData|null $album,
    ) {}

    public static function fromModel(FacilityAlbum $link): self
    {
        return new self(
            id: $link->id,
            facility_id: $link->facility_id,
            album_id: $link->album_id,
            created_at: $link->created_at?->toIso8601String(),
            updated_at: $link->updated_at?->toIso8601String(),
            album: Lazy::whenLoaded('album', $link, fn () => $link->album ? AlbumData::from($link->album) : null),
        );
    }
}
