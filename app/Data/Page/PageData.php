<?php

namespace App\Data\Page;

use App\Models\Page;
use Spatie\LaravelData\Data;

/** Output DTO for a page. Translatable fields are full locale => value maps. */
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
            is_system: $page->is_system,
            thumbnail_url: $page->thumbnail_url,
            created_at: $page->created_at?->toIso8601String(),
            updated_at: $page->updated_at?->toIso8601String(),
        );
    }
}
