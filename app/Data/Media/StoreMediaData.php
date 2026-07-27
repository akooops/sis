<?php

namespace App\Data\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * The one place size/mime are enforced — forms only reference the resulting id.
 * Rules come from config/uploads.php: the type's extensions plus the max size.
 */
class StoreMediaData extends Data
{
    public function __construct(
        public string $type,
        public UploadedFile $file,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $type = is_array($context->payload) ? ($context->payload['type'] ?? null) : null;
        $extensions = is_string($type) ? (array) config("uploads.allowed_types.$type", []) : [];

        // Config is bytes; Laravel's `max` for files is kilobytes.
        $maxKb = (int) floor(((int) config('uploads.max_file_size', 10485760)) / 1024);

        return [
            'type' => ['required', 'string', Rule::in(array_keys((array) config('uploads.allowed_types', [])))],
            'file' => array_values(array_filter([
                'required',
                'file',
                'max:'.$maxKb,
                $extensions ? 'mimes:'.implode(',', $extensions) : null,
            ])),
        ];
    }
}
