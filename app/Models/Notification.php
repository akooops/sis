<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The content of a notification, created once by an observer or job via
 * NotificationService::send() and fanned out through notification_users. The
 * icon comes from the type; route_name/route_params build the click-through.
 *
 * Every notification carries a type — notification_type_id is NOT NULL. The
 * type is what groups subscribe to; a caller may route to extra groups on top
 * of the subscribers (a form's own groups), but never without a type.
 *
 * Append-only with no delete UI, so it is MassPrunable: the daily model:prune
 * builder-deletes rows past notifications.prune_after_days (no events — a
 * per-row prune would write an audit row each), and the pivot follows by FK.
 *
 * Not Laravel's DatabaseNotification: this app ships its own on top of
 * Integrations.
 */
class Notification extends Model
{
    use HasFactory, HasUlids, MassPrunable;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $casts = [
        'route_params' => 'array',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function type(): BelongsTo
    {
        return $this->belongsTo(NotificationType::class, 'notification_type_id');
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

    public function prunable(): Builder
    {
        return static::query()
            ->where('created_at', '<', now()->subDays((int) config('notifications.prune_after_days', 90)));
    }
}
