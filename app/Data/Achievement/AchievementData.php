<?php

namespace App\Data\Achievement;

use App\Models\Achievement;
use App\Models\Media;
use Spatie\LaravelData\Data;

/**
 * Output DTO for an achievement. Note the two independent timelines:
 * `published_at` is when the listing goes live, `achieved_at` is when the thing
 * happened.
 */
class AchievementData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public ?string $category_id,
        public ?string $category_name,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $description,
        /** @var array<string, string|null> */
        public array $content,
        /** @var array<string, string|null> */
        public array $done_by,
        public string $status,
        public ?string $published_at,
        public ?string $achieved_at,
        public ?string $css_url,
        public ?string $custom_css,
        public ?string $thumbnail_url,
        /** @var array<int, array{id: string, url: string|null, name: string}> */
        public array $images,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Achievement $achievement): self
    {
        return new self(
            id: $achievement->id,
            name: $achievement->name,
            slug: $achievement->slug,
            category_id: $achievement->category_id,
            // Flattened rather than a nested object: the table renders a name and
            // the picker only needs the id, so a relation DTO would be ceremony.
            category_name: $achievement->category?->name,
            title: $achievement->getTranslations('title'),
            description: $achievement->getTranslations('description'),
            content: $achievement->getTranslations('content'),
            done_by: $achievement->getTranslations('done_by'),
            status: $achievement->status->getValue(),
            published_at: $achievement->published_at?->toIso8601String(),
            achieved_at: $achievement->achieved_at?->toDateString(),
            css_url: $achievement->css_url,
            custom_css: $achievement->custom_css,
            thumbnail_url: $achievement->thumbnail_url,
            images: $achievement->getMedia(Achievement::IMAGES_COLLECTION)
                ->map(fn (Media $media) => ['id' => $media->id, 'url' => $media->url, 'name' => $media->name])
                ->all(),
            created_at: $achievement->created_at?->toIso8601String(),
            updated_at: $achievement->updated_at?->toIso8601String(),
        );
    }
}
