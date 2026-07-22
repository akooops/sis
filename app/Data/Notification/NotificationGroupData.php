<?php

namespace App\Data\Notification;

use App\Models\NotificationGroup;
use Spatie\LaravelData\Data;

/**
 * Output DTO for a notification group. `type_ids`/`user_ids` prefill the edit
 * form's multi-selects (present only when the relations are loaded — the show
 * endpoint loads them; the index list uses the counts instead).
 */
class NotificationGroupData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $code,
        public ?string $description,
        /** @var array<int, string> */
        public array $type_ids,
        /** @var array<int, string> */
        public array $user_ids,
        /** @var array<int, NotificationTypeData> */
        public array $types,
        /** Preselected members as {value,label} for the edit-form multiselect. @var array<int, array{value: string, label: string}> */
        public array $members,
        public int $types_count,
        public int $members_count,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(NotificationGroup $group): self
    {
        $typesLoaded = $group->relationLoaded('types');
        $usersLoaded = $group->relationLoaded('users');

        return new self(
            id: $group->id,
            name: $group->name,
            code: $group->code,
            description: $group->description,
            type_ids: $typesLoaded ? $group->types->pluck('id')->all() : [],
            user_ids: $usersLoaded ? $group->users->pluck('id')->all() : [],
            types: $typesLoaded ? NotificationTypeData::collect($group->types->all()) : [],
            members: $usersLoaded ? $group->users->map(fn ($u) => ['value' => $u->id, 'label' => $u->username ?? $u->email])->all() : [],
            types_count: $group->types_count ?? ($typesLoaded ? $group->types->count() : 0),
            members_count: $group->users_count ?? ($usersLoaded ? $group->users->count() : 0),
            created_at: $group->created_at?->toIso8601String(),
            updated_at: $group->updated_at?->toIso8601String(),
        );
    }
}
