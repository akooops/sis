<?php

namespace App\Data\Notification;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Compose input. Recipients are NOT listed here — they are resolved from the
 * groups that route this type (NotificationService::send).
 */
class StoreNotificationData extends Data
{
    public function __construct(
        public string $type,
        public string $title,
        public ?string $body,
        public ?string $route_name,
        /** @var array<string, mixed>|null */
        public ?array $route_params,
        public ?string $icon,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'type' => ['required', 'string', Rule::exists('notification_types', 'code')],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'route_name' => ['nullable', 'string', 'max:255'],
            'route_params' => ['nullable', 'array'],
            'icon' => ['nullable', 'string', 'max:255'],
        ];
    }
}
