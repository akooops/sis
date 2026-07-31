<?php

namespace App\Data\Newsletter;

use App\Models\IntegrationType;
use App\Models\Language;
use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Same two switches and two statuses as create, but the title arrives as the full
 * locale map — errors come back keyed `title.ar`. Omitting `file` keeps the
 * current one.
 */
class UpdateNewsletterData extends Data
{
    /**
     * @param  array<string, string|null>  $title
     * @param  array<int, string>  $group_ids
     */
    public function __construct(
        public string $name,
        public ?string $subject,
        public ?string $content,
        public ?string $integration_id,
        public ?string $published_status,
        public ?string $published_at,
        public ?string $sent_status,
        public ?string $sent_at,
        public string|Optional|null $file = null,
        public array $title = [],
        public bool $is_published = false,
        public bool $is_sendable = true,
        public array $group_ids = [],
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $codes = Language::enabledCodes();
        $default = Language::defaultCode();

        // Checkboxes arrive as booleans over JSON but as "1"/"0" over form data.
        $published = filter_var($context->payload['is_published'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $sendable = filter_var($context->payload['is_sendable'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $publishedStatus = $context->payload['published_status'] ?? 'draft';
        $sentStatus = $context->payload['sent_status'] ?? 'draft';

        $rules = [
            'name' => ['required', 'string', 'max:255'],

            // Neither on means the issue goes nowhere and shows nowhere.
            'is_published' => ['required', 'boolean', function ($attribute, $value, $fail) use ($published, $sendable) {
                if (! $published && ! $sendable) {
                    $fail('Turn on publishing, sending, or both — otherwise this newsletter does nothing.');
                }
            }],
            'is_sendable' => ['required', 'boolean'],

            /* --- Website side --- */

            // Already published: the file stays unless a new id is sent.
            'file' => $published
                ? ['sometimes', 'nullable', 'string', new CleanUpload(['documents', 'images'])]
                : ['nullable', 'string', new CleanUpload(['documents', 'images'])],
            // array:en,ar also rejects unknown keys.
            'title' => $published
                ? ['required', 'array:'.implode(',', $codes)]
                : ['sometimes', 'array:'.implode(',', $codes)],

            // Hidden is reachable now — the issue may already have been public.
            // Not published: the controller decides, so whatever arrives is ignored.
            'published_status' => $published
                ? ['required', Rule::in(['draft', 'scheduled', 'published', 'hidden'])]
                : ['nullable', Rule::in(['draft', 'scheduled', 'published', 'hidden'])],
            // Only a schedule asks for a date; picking published is a schedule for now.
            'published_at' => $published && $publishedStatus === 'scheduled'
                ? ['required', 'date', 'after:now']
                : ['nullable', 'date'],

            /* --- Email side --- */

            'subject' => $sendable
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
            'content' => $sendable ? ['required', 'string'] : ['nullable', 'string'],

            // Required on the email side — see StoreNewsletterData.
            'integration_id' => [
                $sendable ? 'required' : 'nullable',
                'string',
                Rule::exists('integrations', 'id')->where(
                    fn ($query) => $query->whereIn('integration_type_id', IntegrationType::query()->where('code', 'email')->select('id'))
                ),
            ],

            'group_ids' => $sendable ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'group_ids.*' => ['string', 'exists:newsletter_groups,id'],

            // 'failed' never comes from a client — only the command writes it.
            'sent_status' => $sendable
                ? ['required', Rule::in(['draft', 'scheduled', 'sent'])]
                : ['nullable', Rule::in(['draft', 'scheduled', 'sent'])],
            // Only a schedule asks for a date; picking sent is a schedule for now.
            'sent_at' => $sendable && $sentStatus === 'scheduled'
                ? ['required', 'date', 'after:now']
                : ['nullable', 'date'],
        ];

        // Per locale, not `title.*` plus an override: merged rule sets read badly.
        foreach ($codes as $code) {
            $rules["title.{$code}"] = $published && $code === $default
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
