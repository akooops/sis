<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Routing config: binds a set of notification types to a set of member users.
 * Emitting a type delivers to every member of every group carrying it — always
 * in-app, plus email when the group has an integration (null = in-app only).
 * Members are a pivot resource, not part of the group form.
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

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function types(): BelongsToMany
    {
        return $this->belongsToMany(NotificationType::class, 'notification_group_notification_types');
    }

    public function groupNotificationTypes(): HasMany
    {
        return $this->hasMany(NotificationGroupNotificationType::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'notification_group_users');
    }

    public function groupUsers(): HasMany
    {
        return $this->hasMany(NotificationGroupUser::class);
    }

    /**
     * Forms whose submissions notify this group — the other end of
     * Form::notificationGroups(). Read-only here: the pivot is written through
     * FormNotificationGroupsController from whichever side the admin is on. It
     * exists so `filter[form_id]` on the index can be a relation filter, which
     * is what makes "the groups this form notifies" a linkable page.
     */
    public function forms(): BelongsToMany
    {
        return $this->belongsToMany(Form::class, 'form_notification_groups');
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /**
     * Set the types to exactly these ids. firstOrCreate fires the pivot observer so
     * an attach is audited; removals go through a builder delete, like syncRoles.
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
}
