<?php

namespace App\Data\JobOffer;

use App\Models\JobOffer;
use App\Models\Language;
use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Every locale at once. Errors come back keyed `title.ar`. */
class UpdateJobOfferData extends Data
{
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
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $description,
        /** @var array<string, string|null> */
        public array $content,
        /** @var array<string, string|null> */
        public array $address,
        /** @var array<string, array<int, string>|null> */
        public array $skills = [],
        public string|Optional|null $thumbnail = null,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $codes = Language::enabledCodes();
        $default = Language::defaultCode();
        $status = $context->payload['status'] ?? 'draft';

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('job_offers', 'slug')->ignore(request()->route('jobOffer')),
            ],

            // Blank resolves to the default category in the controller.
            'category_id' => ['nullable', 'string', Rule::exists('categories', 'id')],

            'employment_type' => ['required', Rule::in(JobOffer::EMPLOYMENT_TYPES)],
            'work_mode' => ['required', Rule::in(JobOffer::WORK_MODES)],
            // Null is "unspecified", 0 is entry level — not the same answer.
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'education_level' => ['nullable', Rule::in(JobOffer::EDUCATION_LEVELS)],
            'start_date' => ['nullable', 'date'],

            'status' => ['required', Rule::in(['draft', 'scheduled', 'published', 'hidden'])],
            'published_at' => $status === 'scheduled'
                ? ['required', 'date', 'after:now']
                : ['nullable', 'date'],
            // An offer that goes live after its own deadline is nonsense.
            'deadline_at' => $status === 'scheduled'
                ? ['nullable', 'date', 'after:published_at']
                : ['nullable', 'date'],

            'css_url' => ['nullable', 'url', 'max:2048'],
            'custom_css' => ['nullable', 'string', 'max:65535'],

            // array:en,ar also rejects unknown keys.
            'title' => ['required', 'array:'.implode(',', $codes)],
            'description' => ['required', 'array:'.implode(',', $codes)],
            'content' => ['required', 'array:'.implode(',', $codes)],
            'address' => ['sometimes', 'array:'.implode(',', $codes)],
            'skills' => ['sometimes', 'array:'.implode(',', $codes)],

            'thumbnail' => ['sometimes', 'nullable', 'string', new CleanUpload('images')],
        ];

        // Per locale, not `title.*` plus an override: merged rule sets read badly.
        foreach ($codes as $code) {
            $isDefault = $code === $default;

            $rules["title.{$code}"] = $isDefault
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];
            $rules["description.{$code}"] = $isDefault
                ? ['required', 'string', 'max:1000']
                : ['nullable', 'string', 'max:1000'];
            $rules["content.{$code}"] = $isDefault
                ? ['required', 'string']
                : ['nullable', 'string'];
            // Optional in every locale, the default one included.
            $rules["address.{$code}"] = ['nullable', 'string', 'max:255'];
            $rules["skills.{$code}"] = ['nullable', 'array'];
            // The separator is barred: it would corrupt the stored round trip.
            $rules["skills.{$code}.*"] = ['string', 'max:100', 'not_regex:/'.preg_quote(JobOffer::SKILLS_SEPARATOR, '/').'/'];
        }

        return $rules;
    }
}
