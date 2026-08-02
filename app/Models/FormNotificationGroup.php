<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A notification group routed to when this form receives a submission.
 *
 * Edited from both ends — the form's own page and the group's — so neither side
 * owns it. The form's OWN audience: these groups are handed to
 * NotificationService::send() on top of whoever subscribes to
 * form.submission_received, so attaching a group here notifies it about this
 * form without subscribing it to every form in the app.
 */
class FormNotificationGroup extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(NotificationGroup::class, 'notification_group_id');
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/
}
