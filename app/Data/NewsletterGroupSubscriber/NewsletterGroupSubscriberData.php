<?php

namespace App\Data\NewsletterGroupSubscriber;

use App\Data\NewsletterGroup\NewsletterGroupData;
use App\Models\NewsletterGroupSubscriber;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/** `group` is Lazy — included only when eager-loaded. */
class NewsletterGroupSubscriberData extends Data
{
    public function __construct(
        public string $id,
        public string $newsletter_group_id,
        public ?string $name,
        public string $email,
        public bool $is_active,
        public ?string $subscribed_at,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|NewsletterGroupData|null $group,
    ) {}

    public static function fromModel(NewsletterGroupSubscriber $subscriber): self
    {
        return new self(
            id: $subscriber->id,
            newsletter_group_id: $subscriber->newsletter_group_id,
            name: $subscriber->name,
            email: $subscriber->email,
            is_active: $subscriber->is_active,
            subscribed_at: $subscriber->subscribed_at?->toIso8601String(),
            created_at: $subscriber->created_at?->toIso8601String(),
            updated_at: $subscriber->updated_at?->toIso8601String(),
            group: Lazy::whenLoaded('group', $subscriber, fn () => $subscriber->group ? NewsletterGroupData::from($subscriber->group) : null),
        );
    }
}
