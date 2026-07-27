<?php

namespace App\Rules;

use App\Models\Media;
use App\Services\Uploads\UploadService;
use App\States\Media\Clean;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Form-side rule for the decoupled upload flow: the form sends a media id, and
 * this checks it is an unattached upload of the expected type that passed the
 * scan. Size and mime were already enforced by the upload endpoint.
 *
 *   new CleanUpload('images')                       // one type
 *   new CleanUpload(['images', 'videos', 'audio'])  // any of several
 *
 * The array form is for a mixed collection like an album gallery, where "image
 * or video or audio, but not a document" is the actual rule.
 */
class CleanUpload implements ValidationRule
{
    /** @var array<int, string> */
    private array $types;

    /**
     * @param  string|array<int, string>  $type
     */
    public function __construct(string|array $type)
    {
        $this->types = (array) $type;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $media = Media::query()->whereKey($value)->first();

        if (! $media) {
            $fail('validation.upload_invalid')->translate();

            return;
        }

        // An unattached upload must match the type; an attached one is reused (copied).
        $isUnattachedTemp = $media->model_id === null
            && $media->collection_name === UploadService::TEMP_COLLECTION;

        if ($isUnattachedTemp && ! in_array($media->getCustomProperty('type'), $this->types, true)) {
            $fail('validation.upload_invalid')->translate();

            return;
        }

        if (! $media->state instanceof Clean) {
            $fail('validation.upload_unscanned')->translate();
        }
    }
}
