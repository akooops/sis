<?php

namespace App\Data\Program;

use App\Models\Program;
use Spatie\LaravelData\Data;

/** Flat: the streams and grades lists are their own endpoints. */
class ProgramData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public int $order,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $subtitle,
        /** @var array<string, string|null> */
        public array $description,
        /** @var array<string, string|null> */
        public array $content,
        public ?string $thumbnail_url,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Program $program): self
    {
        return new self(
            id: $program->id,
            name: $program->name,
            slug: $program->slug,
            order: $program->order,
            title: $program->enabledTranslations('title'),
            subtitle: $program->enabledTranslations('subtitle'),
            description: $program->enabledTranslations('description'),
            content: $program->enabledTranslations('content'),
            thumbnail_url: $program->thumbnail_url,
            created_at: $program->created_at?->toIso8601String(),
            updated_at: $program->updated_at?->toIso8601String(),
        );
    }
}
