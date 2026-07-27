<?php

namespace App\Data\Event;

use App\Models\Event;
use Spatie\LaravelData\Data;

/** start_at/end_at are when it runs; published_at is when the page goes live. */
class EventData extends Data
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
        public ?string $start_at,
        public ?string $end_at,
        public ?string $css_url,
        public ?string $custom_css,
        public ?string $thumbnail_url,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Event $event): self
    {
        return new self(
            id: $event->id,
            name: $event->name,
            slug: $event->slug,
            title: $event->enabledTranslations('title'),
            description: $event->enabledTranslations('description'),
            content: $event->enabledTranslations('content'),
            status: $event->status->getValue(),
            published_at: $event->published_at?->toIso8601String(),
            start_at: $event->start_at?->toIso8601String(),
            end_at: $event->end_at?->toIso8601String(),
            css_url: $event->css_url,
            custom_css: $event->custom_css,
            thumbnail_url: $event->thumbnail_url,
            created_at: $event->created_at?->toIso8601String(),
            updated_at: $event->updated_at?->toIso8601String(),
        );
    }
}
