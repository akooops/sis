<?php

namespace App\Data\Grade;

use App\Data\Program\ProgramData;
use App\Models\Grade;
use App\Models\Media;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/** Output DTO for a grade. `guidelines` is the download list, Lazy and in display order. */
class GradeData extends Data
{
    public function __construct(
        public string $id,
        public string $program_id,
        public string $name,
        public int $order,
        /** @var array<string, string|null> */
        public array $title,
        public ?string $created_at,
        public ?string $updated_at,
        /** @var Lazy|array<int, array{id: string, url: string|null, name: string, type: string|null, mime: string|null}> */
        public Lazy|array $guidelines,
        public Lazy|ProgramData|null $program,
    ) {}

    public static function fromModel(Grade $grade): self
    {
        return new self(
            id: $grade->id,
            program_id: $grade->program_id,
            name: $grade->name,
            order: $grade->order,
            title: $grade->enabledTranslations('title'),
            created_at: $grade->created_at?->toIso8601String(),
            updated_at: $grade->updated_at?->toIso8601String(),
            // type + mime drive the icon the UI picks.
            guidelines: Lazy::whenLoaded('media', $grade, fn () => $grade->getMedia(Grade::GUIDELINES_COLLECTION)
                ->map(fn (Media $media) => [
                    'id' => $media->id,
                    'url' => $media->url,
                    'name' => $media->name,
                    'type' => $media->getCustomProperty('type'),
                    'mime' => $media->mime_type,
                ])->all()),
            program: Lazy::whenLoaded('program', $grade, fn () => $grade->program ? ProgramData::from($grade->program) : null),
        );
    }
}
