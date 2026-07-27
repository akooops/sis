<?php

namespace App\Data\Stream;

use App\Data\Program\ProgramData;
use App\Models\Stream;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/** `order` is read-only here — it is written by the reorder endpoint. */
class StreamData extends Data
{
    public function __construct(
        public string $id,
        public string $program_id,
        public string $name,
        public string $slug,
        public string $color,
        public int $order,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $description,
        /** @var array<string, string|null> */
        public array $content,
        /** @var array<string, string|null> */
        public array $cta,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|ProgramData|null $program,
    ) {}

    public static function fromModel(Stream $stream): self
    {
        return new self(
            id: $stream->id,
            program_id: $stream->program_id,
            name: $stream->name,
            slug: $stream->slug,
            color: $stream->color,
            order: $stream->order,
            title: $stream->enabledTranslations('title'),
            description: $stream->enabledTranslations('description'),
            content: $stream->enabledTranslations('content'),
            cta: $stream->enabledTranslations('cta'),
            created_at: $stream->created_at?->toIso8601String(),
            updated_at: $stream->updated_at?->toIso8601String(),
            program: Lazy::whenLoaded('program', $stream, fn () => $stream->program ? ProgramData::from($stream->program) : null),
        );
    }
}
