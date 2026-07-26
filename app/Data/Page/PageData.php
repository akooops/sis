<?php

namespace App\Data\Page;

use App\Models\Media;
use App\Models\Page;
use Spatie\LaravelData\Data;

/**
 * Output DTO for a page. The three translatable fields are exposed as full
 * locale => value maps so the edit form's language tabs can be seeded in one
 * request.
 *
 * `images` carries the id AND url of every image currently linked to the page, so
 * the form can match them against the content it is editing and drop the ones
 * that are no longer referenced.
 */
class PageData extends Data
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
        public bool $is_system,
        public ?string $thumbnail_url,
        /** @var array<int, array{id: string, url: string|null, name: string}> */
        public array $images,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Page $page): self
    {
        return new self(
            id: $page->id,
            name: $page->name,
            slug: $page->slug,
            title: $page->getTranslations('title'),
            description: $page->getTranslations('description'),
            content: $page->getTranslations('content'),
            status: $page->status->getValue(),
            published_at: $page->published_at?->toIso8601String(),
            css_url: $page->css_url,
            custom_css: $page->custom_css,
            is_system: (bool) $page->is_system,
            thumbnail_url: $page->thumbnail_url,
            images: $page->getMedia(Page::IMAGES_COLLECTION)
                ->map(fn (Media $media) => ['id' => $media->id, 'url' => $media->url, 'name' => $media->name])
                ->all(),
            created_at: $page->created_at?->toIso8601String(),
            updated_at: $page->updated_at?->toIso8601String(),
        );
    }
}
