<?php

namespace App\Data\Event;

use App\Models\Event;
use App\Models\Media;
use Spatie\LaravelData\Data;

/**
 * Output DTO for an event. Note the two independent timelines: `published_at` is
 * when the listing goes live, `start_at`/`end_at` are when the event runs.
 */
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
        /** @var array<int, array{id: string, url: string|null, name: string}> */
        public array $images,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Event $event): self
    {
        return new self(
            id: $event->id,
            name: $event->name,
            slug: $event->slug,
            title: $event->getTranslations('title'),
            description: $event->getTranslations('description'),
            content: $event->getTranslations('content'),
            status: $event->status->getValue(),
            published_at: $event->published_at?->toIso8601String(),
            start_at: $event->start_at?->toIso8601String(),
            end_at: $event->end_at?->toIso8601String(),
            css_url: $event->css_url,
            custom_css: $event->custom_css,
            thumbnail_url: $event->thumbnail_url,
            images: $event->getMedia(Event::IMAGES_COLLECTION)
                ->map(fn (Media $media) => ['id' => $media->id, 'url' => $media->url, 'name' => $media->name])
                ->all(),
            created_at: $event->created_at?->toIso8601String(),
            updated_at: $event->updated_at?->toIso8601String(),
        );
    }
}
