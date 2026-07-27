<?php

namespace App\Data\Article;

use App\Data\Category\CategoryData;
use App\Models\Article;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/** Output DTO for an article. `category` is Lazy — included only when eager-loaded. */
class ArticleData extends Data
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
        public string $status,
        public ?string $published_at,
        public ?string $css_url,
        public ?string $custom_css,
        public ?string $thumbnail_url,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|CategoryData|null $category,
    ) {}

    public static function fromModel(Article $article): self
    {
        return new self(
            id: $article->id,
            name: $article->name,
            slug: $article->slug,
            category_id: $article->category_id,
            title: $article->enabledTranslations('title'),
            description: $article->enabledTranslations('description'),
            content: $article->enabledTranslations('content'),
            status: $article->status->getValue(),
            published_at: $article->published_at?->toIso8601String(),
            css_url: $article->css_url,
            custom_css: $article->custom_css,
            thumbnail_url: $article->thumbnail_url,
            created_at: $article->created_at?->toIso8601String(),
            updated_at: $article->updated_at?->toIso8601String(),
            category: Lazy::whenLoaded('category', $article, fn () => $article->category ? CategoryData::from($article->category) : null),
        );
    }
}
