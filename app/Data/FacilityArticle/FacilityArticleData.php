<?php

namespace App\Data\FacilityArticle;

use App\Data\Article\ArticleData;
use App\Models\FacilityArticle;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * Output DTO for one attachment of a news item to a venue.
 *
 * The property is `article` and the relation is `article` too — PivotDrawer sends
 * that name as `include=` and reads the cell out of it, so the two must agree.
 */
class FacilityArticleData extends Data
{
    public function __construct(
        public string $id,
        public string $facility_id,
        public string $article_id,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|ArticleData|null $article,
    ) {}

    public static function fromModel(FacilityArticle $link): self
    {
        return new self(
            id: $link->id,
            facility_id: $link->facility_id,
            article_id: $link->article_id,
            created_at: $link->created_at?->toIso8601String(),
            updated_at: $link->updated_at?->toIso8601String(),
            article: Lazy::whenLoaded('article', $link, fn () => $link->article ? ArticleData::from($link->article) : null),
        );
    }
}
