<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A routing config: a named group (with a unique code) that binds a set of
 * notification types to a set of member users. Emitting a notification of a type
 * delivers it to every member of every group that includes that type; each
 * membership (NotificationGroupUser) carries the integrations it wants delivery through.
 */
class NotificationGroup extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function types(): BelongsToMany
    {
        return $this->belongsToMany(NotificationType::class, 'notification_group_types');
    }

    public function groupNotificationTypes(): HasMany
    {
        return $this->hasMany(NotificationGroupType::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'notification_group_users');
    }

    public function groupUsers(): HasMany
    {
        return $this->hasMany(NotificationGroupUser::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /**
     * Set the group's notification types to exactly the given ids. firstOrCreate
     * fires the pivot observer per new row so an attach is audited; removals go
     * through a builder delete (unaudited, like syncRoles).
     *
     * @param  array<int, string>  $typeIds
     */
    public function syncTypes(array $typeIds): void
    {
        $this->groupNotificationTypes()
            ->whereNotIn('notification_type_id', $typeIds)
            ->delete();

        foreach ($typeIds as $typeId) {
            $this->groupNotificationTypes()->firstOrCreate(['notification_type_id' => $typeId]);
        }
    }

    /**
     * Set the group's members to exactly the given user ids.
     *
     * @param  array<int, string>  $userIds
     */
    public function syncUsers(array $userIds): void
    {
        $this->groupUsers()
            ->whereNotIn('user_id', $userIds)
            ->delete();

        foreach ($userIds as $userId) {
            $this->groupUsers()->firstOrCreate(['user_id' => $userId]);
        }
    }
}
