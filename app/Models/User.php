<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Models\File;
use App\Models\Permission;
use App\Models\Role;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    
    //Properties
    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = ['fullname', 'avatarUrl'];

    //Relationships
    public function avatar()
    {
        return $this->morphOne(File::class, 'model')->where('is_main', true);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
            ->using(UserRole::class)
            ->withTimestamps();
    }
    
    //Scopes

    //Accessors & Mutators
    public function getFullnameAttribute()
    {
        return "{$this->firstname} {$this->lastname}";
    }

    public function getAvatarUrlAttribute()
    {
        // If user has a profile image, return it
        return ($this->avatar) ? $this->avatar->url : URL::to('assets/images/default-avatar.jpg');
    }

    //Custom Methods
    public function hasRole($role)
    {
        return $this->roles()->where('id', $role)
            ->orWhere('name', $role)
            ->exists();
    }

    public function hasPermission($permission, $module = null)
    {
        $query = $this->roles()
            ->join('role_permissions', 'roles.id', '=', 'role_permissions.role_id')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('permissions.name', $permission);
    
        if ($module) {
            $query->where('permissions.module_id', $module);
        }
            
        return $query->exists();
    }
    
    public function hasAnyPermission($permissions, $module = null)
    {
        $query = $this->roles()
            ->join('role_permissions', 'roles.id', '=', 'role_permissions.role_id')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->whereIn('permissions.name', (array) $permissions);

        if ($module) {
            $query->where('permissions.module_id', $module);
        }

        return $query->exists();
    }
}
