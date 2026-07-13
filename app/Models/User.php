<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, HasUlids, InteractsWithMedia, Notifiable, SoftDeletes;

    // Attributes
    protected $guarded = ['id'];

    protected $appends = ['avatar_url'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'verified_at' => 'datetime',
    ];

    // Media
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    // Spatie never auto-deletes this model's media;, we use observers to frees it on force delete.
    public function shouldDeletePreservingMedia(): bool
    {
        return true;
    }

    // Relationships
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    // Roles
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

    // Permissions
    // A user acts on the web channel, so only web-enabled permissions count.
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

    // Accessors
    public function getAvatarUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('avatar') ?: URL::to('assets/media/avatars/blank.png');
    }
}
