<?php

namespace App\Models;

use App\States\User\Approved;
use App\States\User\UserStatus;
use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;
use Spatie\ModelStates\HasStates;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasMedia, HasStates, HasUlids, Notifiable;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $appends = ['avatar_url'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'status' => UserStatus::class,
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    /**
     * The inbox rows. Named notificationUsers(), not notifications(), so it does not
     * collide with the Notifiable trait's own relation.
     */
    public function notificationUsers(): HasMany
    {
        return $this->hasMany(NotificationUser::class);
    }

    public function notificationGroups(): BelongsToMany
    {
        return $this->belongsToMany(NotificationGroup::class, 'notification_group_users');
    }

    public function groupMemberships(): HasMany
    {
        return $this->hasMany(NotificationGroupUser::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    public function getAvatarUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('avatar') ?: URL::to('assets/admin/media/avatars/blank.png');
    }

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /**
     * Only an approved account may sign in — Verified (Azure confirmed who you are)
     * is not the same as being let in. The single source of truth for the gate.
     */
    public function canLogin(): bool
    {
        return $this->status instanceof Approved;
    }

    /**
     * One avatar: a new one frees the old back to the pool. Deleting a user frees
     * their avatar rather than destroying the file — see UserObserver.
     *
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return ['avatar'];
    }

    /**
     * Set the user's roles to exactly the given ids.
     *
     * @param  array<int, string>  $roleIds
     */
    public function syncRoles(array $roleIds): void
    {
        $this->userRoles()
            ->whereNotIn('role_id', $roleIds)
            ->delete();

        foreach ($roleIds as $roleId) {
            $this->userRoles()->firstOrCreate(['role_id' => $roleId]);
        }
    }

    // Users act on the web channel, so only web-enabled permissions count.
    public function permissions(): array
    {
        $roleIds = $this->roles()->pluck('roles.id');

        return Permission::where('supports_web', true)
            ->whereHas('roles', function ($query) use ($roleIds) {
                $query->whereIn('roles.id', $roleIds);
            })->distinct()->pluck('code')->all();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('permissions.code', $permission)
                    ->where('permissions.supports_web', true);
            })->exists();
    }

    public function hasPermissions(array $permissions): bool
    {
        return array_diff(array_unique($permissions), $this->permissions()) === [];
    }

    /** Unread count, for the bell badge. */
    public function unreadNotificationsCount(): int
    {
        return $this->notificationUsers()->whereNull('read_at')->count();
    }
}
