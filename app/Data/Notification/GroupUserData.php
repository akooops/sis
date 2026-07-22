<?php

namespace App\Data\Notification;

use App\Data\User\UserData;
use App\Models\NotificationGroupUser;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * Output DTO for a membership (notification_group_users) — a member and the integrations their
 * notifications from this group are delivered through. `integration_ids` prefills
 * the per-member integration picker; `integrations` carries names for the chips.
 */
class GroupUserData extends Data
{
    public function __construct(
        public string $id,
        public string $notification_group_id,
        public string $user_id,
        public Lazy|UserData $user,
        /** @var array<int, string> */
        public array $integration_ids,
        /** @var array<int, array{id: string, name: string}> */
        public array $integrations,
        public ?string $created_at,
    ) {}

    public static function fromModel(NotificationGroupUser $groupUser): self
    {
        $integrationsLoaded = $groupUser->relationLoaded('integrations');
        $integrations = $integrationsLoaded ? $groupUser->integrations : collect();

        return new self(
            id: $groupUser->id,
            notification_group_id: $groupUser->notification_group_id,
            user_id: $groupUser->user_id,
            user: Lazy::whenLoaded('user', $groupUser, fn () => UserData::from($groupUser->user)),
            integration_ids: $integrations->pluck('id')->all(),
            integrations: $integrations->map(fn ($i) => ['id' => $i->id, 'name' => $i->name])->values()->all(),
            created_at: $groupUser->created_at?->toIso8601String(),
        );
    }
}
