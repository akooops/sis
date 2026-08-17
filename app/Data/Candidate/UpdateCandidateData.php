<?php

namespace App\Data\Candidate;

use App\Rules\PhoneNumber;
use App\Traits\Phone\NormalizesPhones;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * The only writable half of a candidate — their contact details.
 *
 * THERE IS NO StoreCandidateData. ApplicationProjector is the only thing that
 * creates a person, and there is no `candidates.store` permission to gate one
 * with: a candidate exists because somebody applied.
 *
 * `cv_media_id`, `ai_comment`, `summarised_at`, `embedding` and `embedded_at` are
 * all deliberately absent — the projector and the queues own them, and a form
 * that could write them would let an admin overwrite the scorer's input.
 *
 * NOTE what this fights with: the projector does `$candidate->fill()` over these
 * same columns on EVERY re-projection, so a correction here is reverted the next
 * time that person applies, and editing `email` moves the dedup key that
 * Candidate::scopeIdentifiedBy matches on. Both are stated on the form rather
 * than prevented — a typo'd address is exactly the thing worth being able to fix.
 */
class UpdateCandidateData extends Data
{
    use NormalizesPhones;

    public function __construct(
        public string|Optional $first_name,
        public string|Optional $last_name,
        public string|Optional $email,
        public string|Optional|null $phone,
        public string|Optional|null $address,
        public string|Optional|null $country_id,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        // Ignore this candidate so its own email/phone pass the unique check.
        $candidate = request()->route('candidate');

        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('candidates', 'email')->ignore($candidate)],
            // NormalizesPhones has already rewritten this to E164, so the unique
            // check sees the same shape PhoneType::store() wrote at submit.
            'phone' => ['sometimes', 'nullable', 'string', new PhoneNumber, Rule::unique('candidates', 'phone')->ignore($candidate)],
            'address' => ['sometimes', 'nullable', 'string'],
            'country_id' => ['sometimes', 'nullable', 'string', 'exists:countries,id'],
        ];
    }
}
