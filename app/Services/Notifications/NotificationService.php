<?php

namespace App\Services\Notifications;

use App\Jobs\ShipNotification;
use App\Models\Notification;
use App\Models\NotificationGroup;
use App\Models\NotificationType;
use App\Models\NotificationUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The one way notifications are created — called from observers when a system
 * event happens, never from controllers. Fan-out-on-write:
 *
 *   1. Resolve recipients — the distinct users of every group whose notification
 *      types include the emitted type. Groups are the routing mechanism; the
 *      notification itself is addressed to users, never stored against a group.
 *   2. Create the Notification (content) once plus a notification_users row per
 *      recipient (the in-app inbox — always on), in one transaction.
 *   3. Ship email through each routing group's enabled integration, deduped per
 *      user, via a queued ShipNotification job per user × integration.
 *
 * @see \App\Services\Integrations\Email — the channel the job ships through.
 */
class NotificationService
{
    /**
     * @param  array{title?: string, body?: string|null, route_name?: string|null, route_params?: array<string, mixed>|null}  $attributes
     */
    public static function send(string $typeCode, array $attributes = []): ?Notification
    {
        $type = NotificationType::where('code', $typeCode)->first();

        if (!$type) {
            Log::channel('integrations')->warning('notification.unknown-type', ['type' => $typeCode]);

            return null;
        }

        $groups = NotificationGroup::query()
            ->whereHas('types', fn ($q) => $q->whereKey($type->id))
            ->with(['groupUsers', 'integration' => fn ($q) => $q->where('is_enabled', true)])
            ->get();

        $byUser = [];

        foreach ($groups as $group) {
            foreach ($group->groupUsers as $membership) {
                $byUser[$membership->user_id] ??= [];

                if ($group->integration) {
                    $byUser[$membership->user_id][$group->integration->id] = true;
                }
            }
        }

        $notification = Notification::create([
            'notification_type_id' => $type->id,
            'title' => $attributes['title'] ?? $type->name,
            'body' => $attributes['body'] ?? null,
            'route_name' => $attributes['route_name'] ?? null,
            'route_params' => $attributes['route_params'] ?? null,
        ]);

        foreach (array_keys($byUser) as $userId) {
            NotificationUser::create([
                'notification_id' => $notification->id,
                'user_id' => $userId,
            ]);
        }

        try {
            foreach ($byUser as $userId => $integrations) {
                foreach (array_keys($integrations) as $integrationId) {
                    ShipNotification::dispatch($notification->id, (string) $userId, (string) $integrationId);
                }
            }
        } catch (Throwable $e) {
            Log::channel('integrations')->error('notification.dispatch-failed', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $notification;
    }
}
