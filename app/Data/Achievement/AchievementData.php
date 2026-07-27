<?php

namespace App\Data\Achievement;

use App\Data\Category\CategoryData;
use App\Models\Achievement;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/** achieved_at is when it happened; published_at is when the page goes live. */
class AchievementData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public ?string $category_id,
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
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|CategoryData|null $category,
    ) {}

    public static function fromModel(Achievement $achievement): self
    {
        return new self(
            id: $achievement->id,
            name: $achievement->name,
            slug: $achievement->slug,
            category_id: $achievement->category_id,
            title: $achievement->enabledTranslations('title'),
            description: $achievement->enabledTranslations('description'),
            content: $achievement->enabledTranslations('content'),
            done_by: $achievement->enabledTranslations('done_by'),
            status: $achievement->status->getValue(),
            published_at: $achievement->published_at?->toIso8601String(),
            achieved_at: $achievement->achieved_at?->toDateString(),
            css_url: $achievement->css_url,
            custom_css: $achievement->custom_css,
            thumbnail_url: $achievement->thumbnail_url,
            created_at: $achievement->created_at?->toIso8601String(),
            updated_at: $achievement->updated_at?->toIso8601String(),
            category: Lazy::whenLoaded('category', $achievement, fn () => $achievement->category ? CategoryData::from($achievement->category) : null),
        );
    }
}
