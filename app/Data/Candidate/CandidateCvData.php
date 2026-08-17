<?php

namespace App\Data\Candidate;

use App\Models\Candidate;
use App\Models\Media;
use Illuminate\Support\Facades\URL;
use Spatie\LaravelData\Data;

/**
 * The CV, with a SIGNED, SHORT-LIVED link.
 *
 * The file lives on the private disk and belongs to the form submission that
 * carried it, so Media::url is null for it by design — the only way to the bytes
 * is api.v1.admin.candidates.cv, which is signature- AND permission-gated. Same
 * shape as FormSubmissionData's `files`, and it reuses that TTL rather than
 * introducing a second knob for the same decision.
 *
 * Only ever built from an already-loaded relation. The index does not load `cv`
 * (it would be a query per row) and does not need to: `has_cv` is the whole of
 * what a list can act on.
 */
class CandidateCvData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $mime,
        public int $size,
        public string $url,
    ) {}

    public static function forCandidate(Candidate $candidate, Media $media): self
    {
        $minutes = (int) config('forms.submissions.file_link_ttl', 30);

        return new self(
            id: $media->id,
            name: $media->name,
            mime: $media->mime_type,
            size: (int) $media->size,
            url: URL::temporarySignedRoute(
                'api.v1.admin.candidates.cv',
                now()->addMinutes(max(1, $minutes)),
                ['candidate' => $candidate->id, 'media' => $media->id],
            ),
        );
    }
}
