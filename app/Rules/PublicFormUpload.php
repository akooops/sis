<?php

namespace App\Rules;

use App\Models\Media;
use App\States\Media\Clean;
use App\States\Media\Failed;
use App\States\Media\Pending;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A media id submitted by a PUBLIC form.
 *
 * Stricter than CleanUpload in the one way that matters here: the file must
 * belong to the session that uploaded it. Without that check, anyone could post
 * somebody else's media id and attach a stranger's file to their own submission.
 *
 * Scan states accepted, and why:
 *   Clean   - scanned, safe.
 *   Pending - the scan has not finished. The browser waits a while and then
 *             submits anyway; refusing here would make submission depend on how
 *             fast a virus scanner happens to be.
 *   Failed  - the scanner never returned a verdict. That is a retryable
 *             non-answer, not a positive detection, and refusing it would let a
 *             clamav outage silently block every submission made during it.
 *
 * Infected is the only state refused, because that IS a verdict.
 */
class PublicFormUpload implements ValidationRule
{
    /** @param array<int, string> $extensions */
    public function __construct(
        private string $session,
        private array $extensions = [],
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $media = Media::query()->whereKey($value)->first();

        if (! $media) {
            $fail('validation.upload_invalid')->translate();

            return;
        }

        // Still unattached, and uploaded by this visitor's own session.
        if ($media->model_id !== null || $media->getCustomProperty('form_session') !== $this->session) {
            $fail('validation.upload_invalid')->translate();

            return;
        }

        if (! ($media->state instanceof Clean || $media->state instanceof Pending || $media->state instanceof Failed)) {
            $fail('validation.upload_invalid')->translate();

            return;
        }

        if ($this->extensions !== []) {
            $extension = strtolower(pathinfo((string) $media->name, PATHINFO_EXTENSION));

            if (! in_array($extension, array_map('strtolower', $this->extensions), true)) {
                $fail('validation.upload_invalid')->translate();
            }
        }
    }
}
