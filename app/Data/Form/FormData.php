<?php

namespace App\Data\Form;

use App\Data\Category\CategoryData;
use App\Data\Integration\IntegrationData;
use App\Models\Form;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/** Translatable fields are full locale => value maps. */
class FormData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $description,
        /** @var array<string, string|null> */
        public array $content,
        /** @var array<string, string|null> */
        public array $confirmation_message,
        public string $status,
        public ?string $published_at,
        public ?string $css_url,
        public ?string $custom_css,
        public bool $is_system,

        public bool $is_limited,
        public ?int $submissions_limit,
        public int $submissions_count,

        public bool $is_user_limited,
        public ?int $per_user_limit,
        public string $per_user_limit_by,

        public bool $is_spam_filtered,
        public ?int $min_submit_seconds,
        public bool $is_captcha_enabled,
        public bool $is_ip_stored,

        public string $confirmation_type,
        public ?string $redirect_url,

        public ?string $category_id,
        public ?string $captcha_integration_id,

        public ?string $thumbnail_url,
        public ?string $public_url,

        public int $pages_count,
        public int $fields_count,

        public Lazy|CategoryData|null $category,
        public Lazy|IntegrationData|null $captcha_integration,

        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Form $form): self
    {
        return new self(
            id: $form->id,
            name: $form->name,
            slug: $form->slug,
            title: $form->enabledTranslations('title'),
            description: $form->enabledTranslations('description'),
            content: $form->enabledTranslations('content'),
            confirmation_message: $form->enabledTranslations('confirmation_message'),
            status: $form->status->getValue(),
            published_at: $form->published_at?->toIso8601String(),
            css_url: $form->css_url,
            custom_css: $form->custom_css,
            is_system: (bool) $form->is_system,

            is_limited: (bool) $form->is_limited,
            submissions_limit: $form->submissions_limit,
            submissions_count: (int) $form->submissions_count,

            is_user_limited: (bool) $form->is_user_limited,
            per_user_limit: $form->per_user_limit,
            per_user_limit_by: $form->per_user_limit_by,

            is_spam_filtered: (bool) $form->is_spam_filtered,
            min_submit_seconds: $form->min_submit_seconds,
            is_captcha_enabled: (bool) $form->is_captcha_enabled,
            is_ip_stored: (bool) $form->is_ip_stored,

            confirmation_type: $form->confirmation_type,
            redirect_url: $form->redirect_url,

            category_id: $form->category_id,
            captcha_integration_id: $form->captcha_integration_id,

            thumbnail_url: $form->thumbnail_url,
            // Only meaningful once it is live, and the admin's "copy link" action
            // should not hand out a URL that 404s.
            public_url: $form->isLive() ? url("/forms/{$form->slug}") : null,

            pages_count: (int) ($form->pages_count ?? $form->pages()->count()),
            fields_count: (int) ($form->fields_count ?? $form->fields()->count()),

            category: Lazy::whenLoaded('category', $form, fn () => $form->category ? CategoryData::from($form->category) : null),
            captcha_integration: Lazy::whenLoaded('captchaIntegration', $form, fn () => $form->captchaIntegration ? IntegrationData::from($form->captchaIntegration) : null),

            created_at: $form->created_at?->toIso8601String(),
            updated_at: $form->updated_at?->toIso8601String(),
        );
    }
}
