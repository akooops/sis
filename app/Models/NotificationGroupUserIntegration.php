<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot: an integration a membership delivers through. Audited against the parent
 * group (attached/detached) — see NotificationGroupUserIntegrationObserver.
 */
class NotificationGroupUserIntegration extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function groupUser(): BelongsTo
    {
        return $this->belongsTo(NotificationGroupUser::class, 'notification_group_user_id');
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/
}
