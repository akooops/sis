<?php

namespace App\Data\Newsletter;

use App\Models\IntegrationType;
use App\Rules\CleanUpload;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Two switches decide what is required: is_published wants a file, a title and a
 * publish_status, is_sendable wants a subject, a body, an audience and a status.
 * Create takes the title in the default locale only; the rest come from the edit form.
 */
class StoreNewsletterData extends Data
{
    /**
     * @param  array<int, string>  $group_ids
     */
    public function __construct(
        public string $name,
        public ?string $title,
        public ?string $publish_status,
        public ?string $published_at,
        public ?string $subject,
        public ?string $content,
        public ?string $integration_id,
        public ?string $status,
        public ?string $scheduled_at,
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
        $status = $context->payload['status'] ?? 'draft';
        $publishStatus = $context->payload['publish_status'] ?? 'draft';

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
            'title' => $published
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],

            // The archive pipeline, independent of `status` below.
            // Not published: the controller forces draft and clears the date.
            'publish_status' => $published
                ? ['required', Rule::in(['draft', 'scheduled', 'published', 'hidden'])]
                : ['nullable', Rule::in(['draft', 'scheduled', 'published', 'hidden'])],
            // Only a schedule asks for a date; publishing is stamped by the controller.
            'published_at' => $published
                ? ($publishStatus === 'scheduled' ? ['required', 'date', 'after:now'] : ['nullable', 'date'])
                : ['prohibited'],

            /* --- Email side --- */

            'subject' => $sendable
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
            // Uncapped: longText column, real limit is max_allowed_packet.
            'content' => $sendable ? ['required', 'string'] : ['nullable', 'string'],

            // Null = the app default mailer; anything named must be an email integration.
            'integration_id' => ['nullable', 'string', Rule::exists('integrations', 'id')->where(
                fn ($query) => $query->whereIn('integration_type_id', IntegrationType::query()->where('code', 'email')->select('id'))
            )],

            // A broadcast with no audience is not a broadcast; a publish-only issue has none.
            'group_ids' => $sendable ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'group_ids.*' => ['string', 'exists:newsletter_groups,id'],

            // sending/sent/failed belong to the command and the job, never to a form.
            // Not sendable: the controller forces draft and clears the schedule.
            'status' => $sendable
                ? ['required', Rule::in(['draft', 'scheduled'])]
                : ['nullable', Rule::in(['draft', 'scheduled'])],
            // "Send now" is scheduled with scheduled_at = now, so a date is required here.
            'scheduled_at' => $sendable && $status === 'scheduled'
                ? ['required', 'date', 'after:now']
                : ['nullable', 'date'],
        ];
    }
}
