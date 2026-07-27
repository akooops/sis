<?php

namespace App\Data\JobOffer;

use App\Data\Category\CategoryData;
use App\Models\JobOffer;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * Output DTO for a job offer. `category` is Lazy — included only when eager-loaded.
 *
 * `skills` comes out split into a real array per locale: the ";;;" join is a storage
 * detail for the public site and never leaves the model.
 */
class JobOfferData extends Data
{
    public function __construct(
        public string $id,
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
        /** @var array<string, array<int, string>> */
        public array $skills,
        public ?string $thumbnail_url,
        public bool $is_open,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|CategoryData|null $category,
    ) {}

    public static function fromModel(JobOffer $jobOffer): self
    {
        return new self(
            id: $jobOffer->id,
            name: $jobOffer->name,
            slug: $jobOffer->slug,
            category_id: $jobOffer->category_id,
            employment_type: $jobOffer->employment_type,
            work_mode: $jobOffer->work_mode,
            experience_years: $jobOffer->experience_years,
            education_level: $jobOffer->education_level,
            start_date: $jobOffer->start_date?->toDateString(),
            deadline_at: $jobOffer->deadline_at?->toIso8601String(),
            status: $jobOffer->status->getValue(),
            published_at: $jobOffer->published_at?->toIso8601String(),
            css_url: $jobOffer->css_url,
            custom_css: $jobOffer->custom_css,
            title: $jobOffer->enabledTranslations('title'),
            description: $jobOffer->enabledTranslations('description'),
            content: $jobOffer->enabledTranslations('content'),
            address: $jobOffer->enabledTranslations('address'),
            skills: array_map([JobOffer::class, 'splitSkills'], $jobOffer->enabledTranslations('skills')),
            thumbnail_url: $jobOffer->thumbnail_url,
            is_open: $jobOffer->isOpen(),
            created_at: $jobOffer->created_at?->toIso8601String(),
            updated_at: $jobOffer->updated_at?->toIso8601String(),
            category: Lazy::whenLoaded('category', $jobOffer, fn () => $jobOffer->category ? CategoryData::from($jobOffer->category) : null),
        );
    }
}
