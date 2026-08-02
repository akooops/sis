<?php

namespace App\Data\Form;

use App\Models\Form;
use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Create takes the default locale only for the copy — there is nothing to
 * translate until the form exists — but every SETTING is available here, on the
 * same rules the update payload uses. A limit, a spam filter or a captcha that
 * could only be set after saving meant a form went live for the seconds between
 * the two, unlimited and unguarded.
 */
class StoreFormData extends Data
{
    use ResolvesIntegrationTypes;
    use SanitisesCustomCss;

    public function __construct(
        public string $name,
        public string $slug,
        public string $title,
        public string $description,
        public ?string $content,
        public ?string $confirmation_message,
        public string $status,
        public ?string $published_at,
        public ?string $css_url,
        public ?string $custom_css,
        public ?string $category_id,
        public string $thumbnail,

        public bool $is_limited = false,
        public ?int $submissions_limit = null,
        public bool $is_user_limited = false,
        public ?int $per_user_limit = null,
        public string $per_user_limit_by = 'ip',

        public bool $is_spam_filtered = true,
        public ?int $min_submit_seconds = null,
        public bool $is_captcha_enabled = false,

        public string $confirmation_type = 'message',
        public ?string $redirect_url = null,

        public ?string $captcha_integration_id = null,
        public ?string $analytics_integration_id = null,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $payload = $context->payload;
        $status = $payload['status'] ?? 'draft';

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('forms', 'slug')],

            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'confirmation_message' => ['nullable', 'string'],

            'status' => ['required', Rule::in(['draft', 'scheduled', 'published'])],
            'published_at' => $status === 'scheduled'
                ? ['required', 'date', 'after:now']
                : ['nullable', 'date'],

            'css_url' => ['nullable', 'url:http,https', 'max:2048'],
            // Measured AFTER SanitisesCustomCss has run, so the limit applies to
            // what will actually be stored.
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
             * Type-scoped, exactly as on update: a captcha slot must not accept
             * an analytics integration. Rule::exists('integrations','id') alone
             * would let the two be swapped, and the failure would only show up
             * at submit time on a live public form.
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

            // Required: a form is listed with a picture wherever it is offered,
            // and one added "later" never was.
            'thumbnail' => ['required', 'string', new CleanUpload('images')],
        ];
    }
}
