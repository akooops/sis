<?php

namespace App\Services\Notifications;

use App\Jobs\ShipNotification;
use App\Models\NotificationGroupUser;
use App\Models\Notification;
use App\Models\NotificationGroup;
use App\Models\NotificationType;
use App\Models\NotificationUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * The one way notifications are created. Fan-out-on-write:
 *
 *   1. Resolve recipients — the distinct users of every group whose notification
 *      types include the emitted type. Groups are the routing mechanism; the
 *      notification itself is addressed to users, never stored against a group.
 *   2. Create the Notification (content) once.
 *   3. Write a notification_users row per recipient (the in-app inbox — always).
 *   4. For each recipient, ship through the distinct integrations from the
 *      memberships that routed this type to them, via a queued ShipNotification
 *      job per user × integration.
 *
 * @see \App\Services\Integrations\Email / Sms — the channels the job ships through.
 */
class NotificationService
{
    /**
     * @param  array{title?: string, body?: string|null, data?: array<string, mixed>|null, route_name?: string|null, route_params?: array<string, mixed>|null, icon?: string|null, notifiable?: Model|null}  $attributes
     */
    public static function send(string $typeCode, array $attributes = []): Notification
    {
        $type = NotificationType::where('code', $typeCode)->first();

        // Groups that route this type to their members.
        $groupIds = $type
            ? NotificationGroup::whereHas('types', fn ($q) => $q->whereKey($type->id))->pluck('id')
            : collect();

        // Memberships in those groups, with each member's enabled delivery
        // integrations eager-loaded (so shipping needs no extra query per user).
        $memberships = NotificationGroupUser::query()
            ->whereIn('notification_group_id', $groupIds)
            ->with(['integrations' => fn ($q) => $q->where('is_enabled', true)])
            ->get();

        $notifiable = $attributes['notifiable'] ?? null;

        $notification = Notification::create([
            'type' => $typeCode,
            'title' => $attributes['title'] ?? ($type?->name ?? $typeCode),
            'body' => $attributes['body'] ?? null,
            'data' => $attributes['data'] ?? null,
            'route_name' => $attributes['route_name'] ?? null,
            'route_params' => $attributes['route_params'] ?? null,
            'icon' => $attributes['icon'] ?? null,
            'notifiable_type' => $notifiable?->getMorphClass(),
            'notifiable_id' => $notifiable?->getKey(),
        ]);

        // user_id => [integration_id => Integration], deduped across the user's
        // routing memberships. A user with no integrations still gets a key here,
        // so they still receive the in-app row.
        $byUser = [];
        foreach ($memberships as $membership) {
            $byUser[$membership->user_id] ??= [];
            foreach ($membership->integrations as $integration) {
                $byUser[$membership->user_id][$integration->id] = $integration;
            }
        }

        if ($byUser === []) {
            return $notification;
        }

        // In-app rows in one bulk insert — notification_users is not audited, so
        // skipping model events here is deliberate (and much faster).
        $now = now();
        $rows = [];
        foreach (array_keys($byUser) as $userId) {
            $rows[] = [
                'id' => (string) Str::ulid(),
                'notification_id' => $notification->id,
                'user_id' => $userId,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        NotificationUser::insert($rows);

        // External delivery — queued, one job per user × integration.
        foreach ($byUser as $userId => $integrations) {
            foreach ($integrations as $integrationId => $integration) {
                ShipNotification::dispatch($notification->id, (string) $userId, (string) $integrationId);
            }
        }

        return $notification;
    }
}
