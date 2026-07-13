<?php

namespace App\Rules;

use App\Models\Media;
use App\Services\Uploads\UploadService;
use App\States\Media\Clean;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Form-side rule for the decoupled upload flow: the form sends a media id, and
 * this only checks it is a still-unattached upload of the expected type that
 * passed the malware scan. Size/mime were already enforced by the upload
 * endpoint (see App\Services\Uploads\UploadService). Uploads are not user-scoped.
 */
class CleanUpload implements ValidationRule
{
    public function __construct(
        private string $type,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $media = Media::query()->whereKey($value)->first();

        if (! $media) {
            $fail('validation.upload_invalid')->translate();

            return;
        }

        // A still-unattached temp upload must match the expected type. An
        // already-attached media may be reused as-is (it will be copied).
        $isUnattachedTemp = $media->model_id === null
            && $media->collection_name === UploadService::TEMP_COLLECTION;

        if ($isUnattachedTemp && $media->getCustomProperty('type') !== $this->type) {
            $fail('validation.upload_invalid')->translate();

            return;
        }

        if (! $media->state instanceof Clean) {
            $fail('validation.upload_unscanned')->translate();
        }
    }
}
