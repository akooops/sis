<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A kind of notification the app can emit (system.announcement, user.approved…).
 * A seeded mirror of config('notifications.types'); grown by code, never CRUD.
 * Groups subscribe to types — emitting a notification of a type fans it out to
 * the members of every group that includes it.
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
