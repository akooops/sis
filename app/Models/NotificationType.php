<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A kind of notification (system.announcement, user.approved…), mirroring
 * config('notifications.types'). Grown by code, never CRUD. Groups subscribe to
 * types, and emitting one fans out to their members.
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
