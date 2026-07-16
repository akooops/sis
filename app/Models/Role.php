<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->using(RolePermission::class)
            ->withTimestamps();
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->using(UserRole::class)
            ->withTimestamps();
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /**
     * Set the role's permissions to exactly the given ids.
     *
     * @param  array<int, string>  $permissionIds
     */
    public function syncPermissions(array $permissionIds): void
    {
        $this->rolePermissions()
            ->whereNotIn('permission_id', $permissionIds)
            ->delete();

        foreach ($permissionIds as $permissionId) {
            $this->rolePermissions()->firstOrCreate(['permission_id' => $permissionId]);
        }
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('code', $permission)->exists();
    }

    public function hasPermissions(array $permissions): bool
    {
        $permissions = array_unique($permissions);

        if ($permissions === []) {
            return true;
        }

        return $this->permissions()
            ->whereIn('code', $permissions)
            ->distinct()
            ->count('permissions.code') === count($permissions);
    }
}
