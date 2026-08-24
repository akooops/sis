<?php

namespace App\Data\Facility;

use App\Models\Facility;
use Spatie\LaravelData\Data;

/**
 * Output DTO for a venue.
 *
 * Every `*_count` comes from withCount() and is null when it was not asked for —
 * null is "not counted", 0 is "none", and the index needs to tell those apart to
 * know whether to show a dash or a zero.
 */
class FacilityData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public int $order,
        public string $status,
        public ?string $published_at,
        public ?string $css_url,
        public ?string $custom_css,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $description,
        /** @var array<string, string|null> */
        public array $content,
        public ?string $thumbnail_url,
        public ?int $slots_count,
        public ?int $open_slots_count,
        public ?int $reservations_count,
        public ?int $articles_count,
        public ?int $albums_count,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Facility $facility): self
    {
        return new self(
            id: $facility->id,
            name: $facility->name,
            slug: $facility->slug,
            order: (int) $facility->order,
            status: $facility->status->getValue(),
            published_at: $facility->published_at?->toIso8601String(),
            css_url: $facility->css_url,
            custom_css: $facility->custom_css,
            title: $facility->enabledTranslations('title'),
            description: $facility->enabledTranslations('description'),
            content: $facility->enabledTranslations('content'),
            thumbnail_url: $facility->thumbnail_url,
            slots_count: isset($facility->slots_count) ? (int) $facility->slots_count : null,
            open_slots_count: isset($facility->open_slots_count) ? (int) $facility->open_slots_count : null,
            reservations_count: isset($facility->reservations_count) ? (int) $facility->reservations_count : null,
            articles_count: isset($facility->article_links_count) ? (int) $facility->article_links_count : null,
            albums_count: isset($facility->album_links_count) ? (int) $facility->album_links_count : null,
            created_at: $facility->created_at?->toIso8601String(),
            updated_at: $facility->updated_at?->toIso8601String(),
        );
    }
}
