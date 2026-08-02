<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A kind of notification (user.pending_approval, form.submission_received…),
 * mirroring config('notifications.types'). Grown by code, never CRUD. Groups
 * subscribe to types, and emitting one fans out to their members.
 *
 * There is one flavour and it is subscribable — nothing here is hidden from the
 * picker. A caller may hand send() extra group ids (a form hands in the groups
 * attached to it), but that is on TOP of the subscribers, never instead of the
 * type: every notification carries one.
 */
class NotificationType extends Model
{
    use HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(NotificationGroup::class, 'notification_group_notification_types');
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/
}
