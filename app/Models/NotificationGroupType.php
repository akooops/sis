<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot: a notification type a group sends. Its lifecycle is audited against the
 * parent group (attached/detached) — see NotificationGroupTypeObserver.
 */
class NotificationGroupType extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function group(): BelongsTo
    {
        return $this->belongsTo(NotificationGroup::class, 'notification_group_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(NotificationType::class, 'notification_type_id');
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/
}
