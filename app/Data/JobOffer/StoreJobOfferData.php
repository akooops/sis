<?php

namespace App\Data\JobOffer;

use App\Models\JobOffer;
use App\Rules\CleanUpload;
use App\Traits\Css\SanitisesCustomCss;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Create takes the default locale only; the rest come from the edit form. */
class StoreJobOfferData extends Data
{
    use SanitisesCustomCss;

    public function __construct(
        public string $name,
        public string $slug,
        public ?string $category_id,
        public string $employment_type,
        public string $work_mode,
        public ?int $experience_years,
        public ?string $education_level,
        public ?string $start_date,
        public ?string $deadline_at,
        public string $status,
        public ?string $published_at,
        public ?string $css_url,
        public ?string $custom_css,
        public string $title,
        public string $description,
        public string $content,
        public ?string $address,
        public string $thumbnail,
        /** @var array<int, string> */
        public array $skills = [],
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $status = $context->payload['status'] ?? 'draft';

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('job_offers', 'slug')],

            // Blank resolves to the default category in the controller.
            'category_id' => ['nullable', 'string', Rule::exists('categories', 'id')],

            'employment_type' => ['required', Rule::in(JobOffer::EMPLOYMENT_TYPES)],
            'work_mode' => ['required', Rule::in(JobOffer::WORK_MODES)],
            // Null is "unspecified", 0 is entry level — not the same answer.
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'education_level' => ['nullable', Rule::in(JobOffer::EDUCATION_LEVELS)],
            'start_date' => ['nullable', 'date'],

            // No 'hidden' at birth: nothing public to withdraw yet.
            'status' => ['required', Rule::in(['draft', 'scheduled', 'published'])],
            // Only a schedule needs a date; publishing is stamped by the controller.
            'published_at' => $status === 'scheduled'
                ? ['required', 'date', 'after:now']
                : ['nullable', 'date'],
            // An offer that goes live after its own deadline is nonsense.
            'deadline_at' => $status === 'scheduled'
                ? ['nullable', 'date', 'after:published_at']
                : ['nullable', 'date'],

            'css_url' => ['nullable', 'url:http,https', 'max:2048'],
            // Counted AFTER SanitisesCustomCss has stripped the value, so the
            // limit measures what will be stored — in characters, not bytes: the
            // column is TEXT, so a heavily multibyte sheet can still overflow it.
            'custom_css' => ['nullable', 'string', 'max:65535'],

            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            // Uncapped: JSON column, real limit is max_allowed_packet.
            'content' => ['required', 'string'],
            // Optional: a remote offer has nowhere to be.
            'address' => ['nullable', 'string', 'max:255'],

            'skills' => ['sometimes', 'array'],
            // The separator is barred: it would corrupt the stored round trip.
            'skills.*' => ['string', 'max:100', 'not_regex:/'.preg_quote(JobOffer::SKILLS_SEPARATOR, '/').'/'],

            'thumbnail' => ['required', 'string', new CleanUpload('images')],
        ];
    }
}
