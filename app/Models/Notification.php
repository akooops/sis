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
 * The content of a notification, created once — always by an observer via
 * NotificationService::send(), never by hand — and fanned out to recipients via
 * notification_users rows (the per-user inbox). Display icon comes from the type;
 * `route_name`/`route_params` let the frontend build a Ziggy click-through link.
 *
 * Append-only with no delete UI, so it is MassPrunable: rows older than
 * notifications.prune_after_days go via the daily model:prune as a builder
 * delete (no model events — a per-row prune would write one audit row per
 * notification), and their notification_users rows follow via the FK cascade.
 *
 * Not to be confused with Laravel's own DatabaseNotification — this app ships its
 * own notification system on top of the Integrations module.
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
