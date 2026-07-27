<?php

namespace App\Data\Document;

use App\Rules\CleanUpload;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Create takes the default locale only; the rest come from the edit form. */
class StoreDocumentData extends Data
{
    public function __construct(
        public string $name,
        public string $title,
        public string $file,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            // Any uploadable type: a document may legitimately be a scan or a video.
            'file' => ['required', 'string', new CleanUpload(['documents', 'images', 'videos', 'audio'])],
        ];
    }
}
