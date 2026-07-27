<?php

namespace App\Data\Document;

use App\Models\Document;
use Spatie\LaravelData\Data;

class DocumentData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        /** @var array<string, string|null> */
        public array $title,
        public ?string $file_url,
        public ?string $file_name,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Document $document): self
    {
        $file = $document->getMedia(Document::FILE_COLLECTION)->first();

        return new self(
            id: $document->id,
            name: $document->name,
            title: $document->enabledTranslations('title'),
            file_url: $document->file_url,
            file_name: $file?->name,
            created_at: $document->created_at?->toIso8601String(),
            updated_at: $document->updated_at?->toIso8601String(),
        );
    }
}
