<?php

namespace App\Data\Upload;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Validates an incoming file against config/uploads.php: `type` must be one of
 * the configured categories, and the file must match that category's allowed
 * extensions and the global max size. This is the single place size/mime are
 * enforced (the form only later references the resulting upload id).
 */
class StoreUploadData extends Data
{
    public function __construct(
        public string $type,
        public UploadedFile $file,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $type = is_array($context->payload) ? ($context->payload['type'] ?? null) : null;
        $extensions = is_string($type) ? (array) config("media-library.allowed_types.$type", []) : [];

        // max_file_size is in bytes; Laravel's `max` for files is kilobytes.
        $maxKb = (int) floor(((int) config('media-library.max_file_size', 10485760)) / 1024);

        return [
            'type' => ['required', 'string', Rule::in(array_keys((array) config('media-library.allowed_types', [])))],
            'file' => array_values(array_filter([
                'required',
                'file',
                'max:'.$maxKb,
                $extensions ? 'mimes:'.implode(',', $extensions) : null,
            ])),
        ];
    }
}
