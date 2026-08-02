<?php

namespace App\Services\Notifications;

use App\Jobs\ShipNotification;
use App\Models\Notification;
use App\Models\NotificationGroup;
use App\Models\NotificationType;
use App\Models\NotificationUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The one way notifications are created — called from observers and jobs when a
 * system event happens, never from controllers. Fan-out-on-write:
 *
 *   1. Resolve recipients: every group subscribed to the emitted TYPE, UNION
 *      every group explicitly handed in.
 *   2. Create the Notification (content) once plus a notification_users row per
 *      recipient (the in-app inbox — always on).
 *   3. Ship email through each routing group's enabled integration, deduped per
 *      user, via a queued ShipNotification job per user × integration.
 *
 * send() is the single entry point and the type is always required — every
 * notification carries one, so the inbox always has an icon and a type line.
 * `$groupIds` is an ADDITION, not an alternative: a form hands in the groups
 * attached to it, and those members are notified alongside anyone subscribed to
 * form.submission_received. A user reached by both paths still gets exactly one
 * notification — recipients are keyed by user id below.
 *
 * @see \App\Services\Integrations\Email — the channel the job ships through.
 */
class NotificationService
{
    /**
     * Emit a type to its subscribers, plus the given groups.
     *
     * @param  array{title?: string, body?: string|null, route_name?: string|null, route_params?: array<string, mixed>|null}  $attributes
     * @param  array<int, string>|null  $groupIds  extra groups to notify on top of the type's subscribers
     */
    public static function send(string $typeCode, array $attributes = [], ?array $groupIds = null): ?Notification
    {
        $type = static::type($typeCode);

        if (! $type) {
            return null;
        }

        $groups = static::groups(
            NotificationGroup::query()->where(function ($query) use ($type, $groupIds) {
                $query->whereHas('types', fn ($q) => $q->whereKey($type->id));

                if (filled($groupIds)) {
                    $query->orWhereIn('id', $groupIds);
                }
            })
        );

        return static::dispatch($type, $attributes, $groups);
    }

    protected static function type(string $typeCode): ?NotificationType
    {
        $type = NotificationType::where('code', $typeCode)->first();

        if (! $type) {
            Log::channel('integrations')->warning('notification.unknown-type', ['type' => $typeCode]);
        }

        return $type;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<NotificationGroup>  $query
     * @return Collection<int, NotificationGroup>
     */
    protected static function groups($query): Collection
    {
        return $query
            ->with(['groupUsers', 'integration' => fn ($q) => $q->where('is_enabled', true)])
            ->get();
    }

    /**
     * @param  array{title?: string, body?: string|null, route_name?: string|null, route_params?: array<string, mixed>|null}  $attributes
     * @param  Collection<int, NotificationGroup>  $groups
     */
    protected static function dispatch(NotificationType $type, array $attributes, Collection $groups): Notification
    {
        // Keyed by user id, so a user who is in two of the routed groups — one
        // subscribed to the type, one attached to the form — is one recipient.
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
