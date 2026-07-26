<?php

namespace App\Data\Article;

use App\Models\Article;
use App\Models\Media;
use Spatie\LaravelData\Data;

/**
 * Output DTO for an article. The three translatable fields are exposed as full
 * locale => value maps so the edit form's language tabs can be seeded in one
 * request.
 *
 * `images` carries the id AND url of every image currently linked, so the form
 * can match them against the content it is editing and drop the ones no longer
 * referenced.
 */
class ArticleData extends Data
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
        /** @var array<int, array{id: string, url: string|null, name: string}> */
        public array $images,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Article $article): self
    {
        return new self(
            id: $article->id,
            name: $article->name,
            slug: $article->slug,
            title: $article->getTranslations('title'),
            description: $article->getTranslations('description'),
            content: $article->getTranslations('content'),
            status: $article->status->getValue(),
            published_at: $article->published_at?->toIso8601String(),
            css_url: $article->css_url,
            custom_css: $article->custom_css,
            thumbnail_url: $article->thumbnail_url,
            images: $article->getMedia(Article::IMAGES_COLLECTION)
                ->map(fn (Media $media) => ['id' => $media->id, 'url' => $media->url, 'name' => $media->name])
                ->all(),
            created_at: $article->created_at?->toIso8601String(),
            updated_at: $article->updated_at?->toIso8601String(),
        );
    }
}
