<?php

namespace App\Data\Album;

use App\Models\Album;
use App\Models\Media;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/** Output DTO for an album. `files` is the gallery, Lazy and in display order. */
class AlbumData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $description,
        /** @var array<string, string|null> */
        public array $content,
        public string $status,
        public ?string $published_at,
        public ?string $css_url,
        public ?string $custom_css,
        public ?string $thumbnail_url,
        public ?string $created_at,
        public ?string $updated_at,
        /** @var Lazy|array<int, array{id: string, url: string|null, name: string, type: string|null, mime: string|null}> */
        public Lazy|array $files,
    ) {}

    public static function fromModel(Album $album): self
    {
        return new self(
            id: $album->id,
            name: $album->name,
            slug: $album->slug,
            title: $album->enabledTranslations('title'),
            description: $album->enabledTranslations('description'),
            content: $album->enabledTranslations('content'),
            status: $album->status->getValue(),
            published_at: $album->published_at?->toIso8601String(),
            css_url: $album->css_url,
            custom_css: $album->custom_css,
            thumbnail_url: $album->thumbnail_url,
            created_at: $album->created_at?->toIso8601String(),
            updated_at: $album->updated_at?->toIso8601String(),
            // type + mime drive the thumbnail the UI picks.
            files: Lazy::whenLoaded('media', $album, fn () => $album->getMedia(Album::FILES_COLLECTION)
                ->map(fn (Media $media) => [
                    'id' => $media->id,
                    'url' => $media->url,
                    'name' => $media->name,
                    'type' => $media->getCustomProperty('type'),
                    'mime' => $media->mime_type,
                ])->all()),
        );
    }
}
