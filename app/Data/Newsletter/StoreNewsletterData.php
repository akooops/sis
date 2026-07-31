<?php

namespace App\Data\Newsletter;

use App\Models\IntegrationType;
use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Two switches decide what is required: is_published wants a file and a title,
 * is_sendable a subject, a body and an audience. Each switch also owns its own
 * status and date. Create takes the title in the default locale only; the rest
 * come from the edit form.
 */
class StoreNewsletterData extends Data
{
    /**
     * @param  array<int, string>  $group_ids
     */
    public function __construct(
        public string $name,
        public ?string $title,
        public ?string $subject,
        public ?string $content,
        public ?string $integration_id,
        public ?string $published_status,
        public ?string $published_at,
        public ?string $sent_status,
        public ?string $sent_at,
        public ?string $file = null,
        public bool $is_published = false,
        public bool $is_sendable = true,
        public array $group_ids = [],
    ) {}

    public static function rules(ValidationContext $context): array
    {
        // Checkboxes arrive as booleans over JSON but as "1"/"0" over form data.
        $published = filter_var($context->payload['is_published'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $sendable = filter_var($context->payload['is_sendable'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $publishedStatus = $context->payload['published_status'] ?? 'draft';
        $sentStatus = $context->payload['sent_status'] ?? 'draft';

        return [
            'name' => ['required', 'string', 'max:255'],

            // Neither on means the issue goes nowhere and shows nowhere.
            'is_published' => ['required', 'boolean', function ($attribute, $value, $fail) use ($published, $sendable) {
                if (! $published && ! $sendable) {
                    $fail('Turn on publishing, sending, or both — otherwise this newsletter does nothing.');
                }
            }],
            'is_sendable' => ['required', 'boolean'],

            /* --- Website side --- */

            // The issue itself: usually a PDF, sometimes a scan.
            'file' => $published
                ? ['required', 'string', new CleanUpload(['documents', 'images'])]
                : ['nullable', 'string', new CleanUpload(['documents', 'images'])],
            // A send-only issue is never listed, so it is never asked for a title.
            'title' => $published
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],

            // No 'hidden' at birth: nothing public to withdraw yet. Not published:
            // the controller forces draft, so whatever arrives is ignored.
            'published_status' => $published
                ? ['required', Rule::in(['draft', 'scheduled', 'published'])]
                : ['nullable', Rule::in(['draft', 'scheduled', 'published'])],
            // Only a schedule asks for a date; picking published is a schedule for now.
            'published_at' => $published && $publishedStatus === 'scheduled'
                ? ['required', 'date', 'after:now']
                : ['nullable', 'date'],

            /* --- Email side --- */

            'subject' => $sendable
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
            // Uncapped: longText column, real limit is max_allowed_packet.
            'content' => $sendable ? ['required', 'string'] : ['nullable', 'string'],

            // Required on the email side: the account a broadcast goes out from is
            // a decision to make, not one to inherit from whatever the app default
            // happens to be. Must be an email integration.
            'integration_id' => [
                $sendable ? 'required' : 'nullable',
                'string',
                Rule::exists('integrations', 'id')->where(
                    fn ($query) => $query->whereIn('integration_type_id', IntegrationType::query()->where('code', 'email')->select('id'))
                ),
            ],

            // A broadcast with no audience is not a broadcast; a publish-only issue has none.
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
    }
}
