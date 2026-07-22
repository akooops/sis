<?php

namespace App\Data\Notification;

use App\Models\NotificationUser;
use Spatie\LaravelData\Data;

/**
 * Output DTO for one inbox row (the bell drawer + notifications page). Flattens
 * the notification_users pivot with its notification so the frontend has the read
 * state, the content, the type's icon, and the Ziggy route to click through to —
 * all in one row. `id` is the NOTIFICATION id — the read/delete endpoints take
 * it and resolve the current user's own row server-side.
 */
class NotificationData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $body,
        public ?string $icon,
        public ?string $type_name,
        public ?string $route_name,
        /** @var array<string, mixed>|null */
        public ?array $route_params,
        public bool $is_read,
        public ?string $read_at,
        public ?string $created_at,
    ) {}

    public static function fromModel(NotificationUser $row): self
    {
        $notification = $row->notification;

        return new self(
            id: $row->notification_id,
            title: $notification?->title ?? '',
            body: $notification?->body,
            icon: $notification?->type?->icon,
            type_name: $notification?->type?->name,
            route_name: $notification?->route_name,
            route_params: $notification?->route_params,
            is_read: $row->read_at !== null,
            read_at: $row->read_at?->toIso8601String(),
            created_at: $row->created_at?->toIso8601String(),
        );
    }
}
