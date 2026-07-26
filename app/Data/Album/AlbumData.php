<?php

namespace App\Data\Album;

use App\Models\Album;
use App\Models\Media;
use Spatie\LaravelData\Data;

/**
 * Output DTO for an album. `files` is the gallery itself, in display order
 * (HasMedia::getMedia sorts by order_column, which UploadService::sync writes);
 * `images` is the separate set the editor inserted into the content.
 */
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
        /** @var array<int, array{id: string, url: string|null, name: string, type: string|null, mime: string|null}> */
        public array $files,
        /** @var array<int, array{id: string, url: string|null, name: string}> */
        public array $images,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Album $album): self
    {
        $files = $album->getMedia(Album::FILES_COLLECTION);

        return new self(
            id: $album->id,
            name: $album->name,
            slug: $album->slug,
            title: $album->getTranslations('title'),
            description: $album->getTranslations('description'),
            content: $album->getTranslations('content'),
            status: $album->status->getValue(),
            published_at: $album->published_at?->toIso8601String(),
            css_url: $album->css_url,
            custom_css: $album->custom_css,
            thumbnail_url: $album->thumbnail_url,
            // type + mime so MediaThumb can pick an image, a video frame or an icon.
            files: $files->map(fn (Media $media) => [
                'id' => $media->id,
                'url' => $media->url,
                'name' => $media->name,
                'type' => $media->getCustomProperty('type'),
                'mime' => $media->mime_type,
            ])->all(),
            images: $album->getMedia(Album::IMAGES_COLLECTION)
                ->map(fn (Media $media) => ['id' => $media->id, 'url' => $media->url, 'name' => $media->name])
                ->all(),
            created_at: $album->created_at?->toIso8601String(),
            updated_at: $album->updated_at?->toIso8601String(),
        );
    }
}
