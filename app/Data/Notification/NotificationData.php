<?php

namespace App\Data\Notification;

use App\Enums\MorphType;
use App\Models\Notification;
use Spatie\LaravelData\Data;

/**
 * Output DTO for a notification (the admin compose history). The polymorphic
 * subject is exposed by its public MorphType alias, never a class name.
 */
class NotificationData extends Data
{
    public function __construct(
        public string $id,
        public string $type,
        public string $title,
        public ?string $body,
        /** @var array<string, mixed>|null */
        public ?array $data,
        public ?string $route_name,
        /** @var array<string, mixed>|null */
        public ?array $route_params,
        public ?string $icon,
        public ?string $notifiable_type,
        public ?string $notifiable_id,
        public int $recipients_count,
        public ?string $created_at,
    ) {}

    public static function fromModel(Notification $notification): self
    {
        return new self(
            id: $notification->id,
            type: $notification->type,
            title: $notification->title,
            body: $notification->body,
            data: $notification->data,
            route_name: $notification->route_name,
            route_params: $notification->route_params,
            icon: $notification->icon,
            notifiable_type: MorphType::aliasFor($notification->notifiable_type),
            notifiable_id: $notification->notifiable_id,
            recipients_count: $notification->notification_users_count
                ?? $notification->notificationUsers()->count(),
            created_at: $notification->created_at?->toIso8601String(),
        );
    }
}
