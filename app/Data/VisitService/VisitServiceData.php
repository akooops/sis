<?php

namespace App\Data\VisitService;

use App\Models\VisitService;
use Spatie\LaravelData\Data;

/**
 * Output DTO for a visit service.
 *
 * `slots_count` and `open_slots_count` come from withCount() and are null when it
 * was not asked for — null is "not counted", 0 is "none", and the index needs to
 * tell those apart to know whether to show a dash or a zero.
 */
class VisitServiceData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public int $duration_minutes,
        public int $max_visitors,
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
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(VisitService $service): self
    {
        return new self(
            id: $service->id,
            name: $service->name,
            slug: $service->slug,
            duration_minutes: (int) $service->duration_minutes,
            max_visitors: (int) $service->max_visitors,
            order: (int) $service->order,
            status: $service->status->getValue(),
            published_at: $service->published_at?->toIso8601String(),
            css_url: $service->css_url,
            custom_css: $service->custom_css,
            title: $service->enabledTranslations('title'),
            description: $service->enabledTranslations('description'),
            content: $service->enabledTranslations('content'),
            thumbnail_url: $service->thumbnail_url,
            slots_count: isset($service->slots_count) ? (int) $service->slots_count : null,
            open_slots_count: isset($service->open_slots_count) ? (int) $service->open_slots_count : null,
            reservations_count: isset($service->reservations_count) ? (int) $service->reservations_count : null,
            created_at: $service->created_at?->toIso8601String(),
            updated_at: $service->updated_at?->toIso8601String(),
        );
    }
}
