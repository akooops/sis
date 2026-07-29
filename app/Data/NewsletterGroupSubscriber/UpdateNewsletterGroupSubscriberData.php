<?php

namespace App\Data\NewsletterGroupSubscriber;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Moving a subscriber to another list is a plain newsletter_group_id change. */
class UpdateNewsletterGroupSubscriberData extends Data
{
    public function __construct(
        public string $newsletter_group_id,
        public string $email,
        public ?string $name = null,
        public ?string $subscribed_at = null,
        public bool $is_active = true,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'newsletter_group_id' => ['required', 'string', Rule::exists('newsletter_groups', 'id')],

            // Unique within the list only: the same person may join two of them.
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('newsletter_group_subscribers', 'email')
                    ->where('newsletter_group_id', $context->payload['newsletter_group_id'] ?? null)
                    ->ignore(request()->route('newsletter_group_subscriber')),
            ],
            'name' => ['nullable', 'string', 'max:255'],

            'is_active' => ['sometimes', 'boolean'],
            'subscribed_at' => ['nullable', 'date'],
        ];
    }
}
