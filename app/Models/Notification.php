<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * The content of a notification, created once and fanned out to recipients via
 * notification_users rows (the per-user inbox). `type` is a NotificationType code
 * (resolved by code, like integrations.driver); `route_name`/`route_params` let
 * the frontend build a Ziggy click-through link.
 *
 * Not to be confused with Laravel's own DatabaseNotification — this app ships its
 * own notification system on top of the Integrations module.
 */
class Notification extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $casts = [
        'data' => 'array',
        'route_params' => 'array',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function notificationUsers(): HasMany
    {
        return $this->hasMany(NotificationUser::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'notification_users')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/
}
