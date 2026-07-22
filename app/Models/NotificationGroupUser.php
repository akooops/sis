<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A membership: one user in one notification group. Carries the set of
 * integrations that user wants this group's notifications delivered through
 * (besides the always-on in-app inbox).
 */
class NotificationGroupUser extends Model
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function integrations(): BelongsToMany
    {
        return $this->belongsToMany(Integration::class, 'notification_group_user_integrations');
    }

    public function groupUserIntegrations(): HasMany
    {
        return $this->hasMany(NotificationGroupUserIntegration::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /**
     * Set this membership's delivery integrations to exactly the given ids.
     * firstOrCreate fires the pivot observer per new row (attach audited);
     * removals go through a builder delete (unaudited, like syncRoles/syncTypes).
     *
     * @param  array<int, string>  $integrationIds
     */
    public function syncIntegrations(array $integrationIds): void
    {
        $this->groupUserIntegrations()
            ->whereNotIn('integration_id', $integrationIds)
            ->delete();

        foreach ($integrationIds as $integrationId) {
            $this->groupUserIntegrations()->firstOrCreate(['integration_id' => $integrationId]);
        }
    }
}
