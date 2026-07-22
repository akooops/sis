<?php

namespace App\Data\Notification;

use App\Data\Integration\IntegrationData;
use App\Models\NotificationGroup;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * Output DTO for a notification group. `type_ids` prefills the edit form's type
 * multiselect and `integration_id`/`integration` its email-integration select;
 * `integration` is the full IntegrationData, present when the relation is loaded
 * (null when the group is in-app only). Members are a separate pivot resource
 * (notification-group-users), so only their count appears here.
 */
class NotificationGroupData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $code,
        public ?string $integration_id,
        public Lazy|IntegrationData|null $integration,
        /** @var array<int, string> */
        public array $type_ids,
        /** @var array<int, NotificationTypeData> */
        public array $types,
        public int $types_count,
        public int $members_count,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(NotificationGroup $group): self
    {
        $typesLoaded = $group->relationLoaded('types');

        return new self(
            id: $group->id,
            name: $group->name,
            code: $group->code,
            integration_id: $group->integration_id,
            type_ids: $typesLoaded ? $group->types->pluck('id')->all() : [],
            types: $typesLoaded ? NotificationTypeData::collect($group->types->all()) : [],
            types_count: $group->types_count ?? ($typesLoaded ? $group->types->count() : 0),
            members_count: $group->users_count ?? 0,
            created_at: $group->created_at?->toIso8601String(),
            updated_at: $group->updated_at?->toIso8601String(),
            integration: Lazy::whenLoaded('integration', $group, fn () => $group->integration ? IntegrationData::from($group->integration) : null),
        );
    }
}
