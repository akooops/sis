<?php

namespace App\Data\FacilityArticle;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** A bulk attach: PivotDrawer posts every id the multi-select is holding. */
class StoreFacilityArticleData extends Data
{
    /**
     * @param  array<int, string>  $articles
     */
    public function __construct(public array $articles) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'articles' => ['required', 'array'],
            'articles.*' => ['string', 'exists:articles,id'],
        ];
    }
}
