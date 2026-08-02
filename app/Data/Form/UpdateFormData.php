<?php

namespace App\Data\Form;

use App\Models\Form;
use App\Models\Language;
use App\Rules\CleanUpload;
use App\Traits\Css\SanitisesCustomCss;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Every locale at once. Errors come back keyed `title.ar`. */
class UpdateFormData extends Data
{
    use ResolvesIntegrationTypes;
    use SanitisesCustomCss;

    public function __construct(
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
        public ?string $category_id,

        public bool $is_limited,
        public ?int $submissions_limit,
        public bool $is_user_limited,
        public ?int $per_user_limit,
        public string $per_user_limit_by,

        public bool $is_spam_filtered,
        public ?int $min_submit_seconds,
        public bool $is_captcha_enabled,
        public bool $is_ip_stored,

        public string $confirmation_type,
        public ?string $redirect_url,

        public ?string $captcha_integration_id,
        public ?string $analytics_integration_id,

        public string|Optional|null $thumbnail = null,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $codes = Language::enabledCodes();
        $default = Language::defaultCode();
        $payload = $context->payload;
        $status = $payload['status'] ?? 'draft';

        /** @var Form|null $form */
        $form = request()->route('form');

        $rules = [
            'name' => ['required', 'string', 'max:255'],

            /*
             * A system form's slug is frozen: the app resolves it by slug, so
             * renaming it breaks whatever depends on it. Enforced HERE rather
             * than by hiding the input — the client is not a guard, and the
             * permission gate is off in dev.
             */
            'slug' => $form?->is_system
                ? ['required', Rule::in([$form->slug])]
                : ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('forms', 'slug')->ignore($form)],

            // array:en,ar also rejects unknown keys.
            'title' => ['required', 'array:'.implode(',', $codes)],
            'description' => ['required', 'array:'.implode(',', $codes)],
            'content' => ['required', 'array:'.implode(',', $codes)],
            'confirmation_message' => ['required', 'array:'.implode(',', $codes)],

            'status' => ['required', Rule::in(['draft', 'scheduled', 'published', 'hidden'])],
            'published_at' => $status === 'scheduled'
                ? ['required', 'date', 'after:now']
                : ['nullable', 'date'],

            'css_url' => ['nullable', 'url:http,https', 'max:2048'],
            // Counted AFTER SanitisesCustomCss has stripped the value, so the
            // limit measures what will be stored — in characters, not bytes: the
            // column is TEXT, so a heavily multibyte sheet can still overflow it.
            'custom_css' => ['nullable', 'string', 'max:65535'],

            'category_id' => ['nullable', 'string', Rule::exists('categories', 'id')],

            'is_limited' => ['required', 'boolean'],
            'submissions_limit' => ($payload['is_limited'] ?? false)
                ? ['required', 'integer', 'min:1']
                : ['nullable', 'integer', 'min:1'],

            'is_user_limited' => ['required', 'boolean'],
            'per_user_limit' => ($payload['is_user_limited'] ?? false)
                ? ['required', 'integer', 'min:1']
                : ['nullable', 'integer', 'min:1'],
            'per_user_limit_by' => ['required', Rule::in(Form::LIMIT_BY)],

            'is_spam_filtered' => ['required', 'boolean'],
            // Null means "use the configured default", which is a real choice —
            // hence nullable even when the filter is on.
            'min_submit_seconds' => ['nullable', 'integer', 'min:1', 'max:600'],

            'is_captcha_enabled' => ['required', 'boolean'],
            'is_ip_stored' => ['required', 'boolean'],

            'confirmation_type' => ['required', Rule::in(Form::CONFIRMATION_TYPES)],
            'redirect_url' => ($payload['confirmation_type'] ?? 'message') === 'redirect'
                ? ['required', 'url:http,https', 'max:2048']
                : ['nullable', 'url:http,https', 'max:2048'],

            /*
             * Type-scoped: a captcha slot must not accept an analytics
             * integration. Rule::exists('integrations','id') alone would let the
             * two be swapped, and the failure would only show up at submit time
             * on a live public form.
             */
            'captcha_integration_id' => [
                ($payload['is_captcha_enabled'] ?? false) ? 'required' : 'nullable',
                'string',
                Rule::exists('integrations', 'id')->where(
                    fn ($q) => $q->whereIn('integration_type_id', self::typeIds('captcha')),
                ),
            ],
            'analytics_integration_id' => [
                'nullable',
                'string',
                Rule::exists('integrations', 'id')->where(
                    fn ($q) => $q->whereIn('integration_type_id', self::typeIds('analytics')),
                ),
            ],

            /*
             * Required, like create — but "required" here means the form must
             * END UP with one, not that every save re-picks it. A form that
             * already has a thumbnail may omit the key (that is how the editor
             * says "keep the current file"); it may not send null or an empty
             * string. A form that has none must supply one.
             */
            'thumbnail' => $form?->thumbnail_url
                ? ['sometimes', 'required', 'string', new CleanUpload('images')]
                : ['required', 'string', new CleanUpload('images')],
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
            $rules["content.{$code}"] = ['nullable', 'string'];
            $rules["confirmation_message.{$code}"] = $isDefault
                ? ['required', 'string', 'max:5000']
                : ['nullable', 'string', 'max:5000'];
        }

        return $rules;
    }
}
