<?php

namespace App\Data\Candidate;

use App\Data\Cluster\ClusterData;
use App\Data\Country\CountryData;
use App\Models\Candidate;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * Output DTO for a candidate — the person, not an application.
 *
 * THE EMBEDDING NEVER LEAVES THE SERVER. `candidates.embedding` is 256 floats,
 * which across a 15-row page is ~60 kB of machine state describing nothing a
 * human reads. `is_embedded` is the whole of what the UI needs: whether this
 * person can take part in clustering and matching yet. Same reasoning as `has_ip`
 * on FormSubmissionData — a DTO that named the column would quietly undo the
 * decision not to expose it.
 *
 * `years_of_experience` and the child collections are all guarded on the relation
 * being loaded. Candidate::yearsOfExperience() iterates `$this->experiences`,
 * which LAZY-LOADS if absent — on an index that is one query per row, so the
 * index deliberately does not load it and this comes back null there.
 */
class CandidateData extends Data
{
    public function __construct(
        public string $id,
        public string $first_name,
        public string $last_name,
        public string $full_name,
        public string $email,
        public ?string $phone,
        public ?string $address,
        public ?string $country_id,
        public ?string $country_name,
        public bool $has_cv,
        public ?string $ai_comment,
        public ?string $summarised_at,
        public bool $is_embedded,
        public ?string $embedded_at,
        public ?int $years_of_experience,
        public ?int $applications_count,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|CountryData|null $country,
        public Lazy|CandidateCvData|null $cv,
        /** @var Lazy|array<int, CandidateEducationData> */
        public Lazy|array $educations,
        /** @var Lazy|array<int, CandidateExperienceData> */
        public Lazy|array $experiences,
        /** @var Lazy|array<int, CandidateLanguageData> */
        public Lazy|array $languages,
        /** @var Lazy|array<int, CandidateSkillData> */
        public Lazy|array $skills,
        /** @var Lazy|array<int, CandidateApplicationData> */
        public Lazy|array $applications,
        /** @var Lazy|array<int, ClusterData> */
        public Lazy|array $clusters,
    ) {}

    public static function fromModel(Candidate $candidate): self
    {
        return new self(
            id: $candidate->id,
            first_name: $candidate->first_name,
            last_name: $candidate->last_name,
            full_name: $candidate->full_name,
            email: $candidate->email,
            phone: $candidate->phone,
            address: $candidate->address,
            country_id: $candidate->country_id,
            // The name without paying for the relation on a row that did not load it.
            country_name: $candidate->relationLoaded('country') ? $candidate->country?->name : null,
            // A pointer check, not a relation read — true on the index, where `cv`
            // is never loaded.
            has_cv: $candidate->cv_media_id !== null,
            ai_comment: $candidate->ai_comment,
            summarised_at: $candidate->summarised_at?->toIso8601String(),
            is_embedded: $candidate->embedding !== null,
            embedded_at: $candidate->embedded_at?->toIso8601String(),
            years_of_experience: $candidate->relationLoaded('experiences') ? $candidate->yearsOfExperience() : null,
            // withCount() only; null means the caller did not ask, which is not
            // the same as zero applications.
            applications_count: $candidate->applications_count !== null ? (int) $candidate->applications_count : null,
            created_at: $candidate->created_at?->toIso8601String(),
            updated_at: $candidate->updated_at?->toIso8601String(),
            country: Lazy::whenLoaded('country', $candidate, fn () => $candidate->country ? CountryData::from($candidate->country) : null),
            cv: Lazy::whenLoaded('cv', $candidate, fn () => $candidate->cv ? CandidateCvData::forCandidate($candidate, $candidate->cv) : null),
            educations: Lazy::whenLoaded('educations', $candidate, fn () => CandidateEducationData::collect($candidate->educations->all())),
            experiences: Lazy::whenLoaded('experiences', $candidate, fn () => CandidateExperienceData::collect($candidate->experiences->all())),
            languages: Lazy::whenLoaded('languages', $candidate, fn () => CandidateLanguageData::collect($candidate->languages->all())),
            skills: Lazy::whenLoaded('skills', $candidate, fn () => CandidateSkillData::collect($candidate->skills->all())),
            applications: Lazy::whenLoaded('applications', $candidate, fn () => CandidateApplicationData::collect($candidate->applications->all())),
            // The pools this person landed in. Read-only either way: membership is
            // rewritten wholesale by the nightly rebuild.
            clusters: Lazy::whenLoaded('clusters', $candidate, fn () => ClusterData::collect($candidate->clusters->all())),
        );
    }
}
