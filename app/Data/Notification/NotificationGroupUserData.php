<?php

namespace App\Data\Notification;

use App\Data\User\UserData;
use App\Models\NotificationGroupUser;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

class NotificationGroupUserData extends Data
{
    public function __construct(
        public string $id,
        public string $notification_group_id,
        public string $user_id,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|NotificationGroupData $notification_group,
        public Lazy|UserData $user,
    ) {}

    public static function fromModel(NotificationGroupUser $groupUser): self
    {
        return new self(
            id: $groupUser->id,
            notification_group_id: $groupUser->notification_group_id,
            user_id: $groupUser->user_id,
            created_at: $groupUser->created_at?->toIso8601String(),
            updated_at: $groupUser->updated_at?->toIso8601String(),
            notification_group: Lazy::whenLoaded('group', $groupUser, fn () => NotificationGroupData::from($groupUser->group)),
            user: Lazy::whenLoaded('user', $groupUser, fn () => UserData::from($groupUser->user)),
        );
    }
}
