<?php

namespace App\Data\Newsletter;

use App\Data\Integration\IntegrationData;
use App\Data\NewsletterGroup\NewsletterGroupData;
use App\Models\Newsletter;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * Output DTO for a newsletter. `is_published` drives the website side (title +
 * file), `is_sendable` the email side (subject, content, groups); `group_ids`
 * feeds the form multiselect and `integration` is null on the default mailer.
 *
 * Two sides, never mixed: published_status + published_at is the archive,
 * sent_status + sent_at the broadcast. Each date is the schedule while pending and
 * the moment it happened once done.
 */
class NewsletterData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $published_status,
        public ?string $published_at,
        public string $sent_status,
        public ?string $sent_at,
        public bool $is_published,
        public bool $is_sendable,
        /** @var array<string, string|null> */
        public array $title,
        public ?string $file_url,
        public ?string $file_name,
        public ?string $subject,
        public ?string $content,
        public ?string $integration_id,
        /** @var array<int, string> */
        public array $group_ids,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|IntegrationData|null $integration,
        /** @var Lazy|array<int, NewsletterGroupData> */
        public Lazy|array $groups,
    ) {}

    public static function fromModel(Newsletter $newsletter): self
    {
        $groupsLoaded = $newsletter->relationLoaded('groups');
        $file = $newsletter->getMedia(Newsletter::FILE_COLLECTION)->first();

        return new self(
            id: $newsletter->id,
            name: $newsletter->name,
            published_status: $newsletter->published_status->getValue(),
            published_at: $newsletter->published_at?->toIso8601String(),
            sent_status: $newsletter->sent_status->getValue(),
            sent_at: $newsletter->sent_at?->toIso8601String(),
            is_published: (bool) $newsletter->is_published,
            is_sendable: (bool) $newsletter->is_sendable,
            title: $newsletter->enabledTranslations('title'),
            file_url: $newsletter->file_url,
            // The original filename, for display next to the link.
            file_name: $file?->name,
            subject: $newsletter->subject,
            content: $newsletter->content,
            integration_id: $newsletter->integration_id,
            group_ids: $groupsLoaded ? $newsletter->groups->pluck('id')->all() : [],
            created_at: $newsletter->created_at?->toIso8601String(),
            updated_at: $newsletter->updated_at?->toIso8601String(),
            integration: Lazy::whenLoaded('integration', $newsletter, fn () => $newsletter->integration ? IntegrationData::from($newsletter->integration) : null),
            groups: Lazy::whenLoaded('groups', $newsletter, fn () => NewsletterGroupData::collect($newsletter->groups->all())),
        );
    }
}
