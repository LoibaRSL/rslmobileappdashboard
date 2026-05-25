<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'department',
        'role',
        'wso2_id',
        'wso2_username',
        'wso2_attributes',
        'last_login_at',
        'is_active'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'wso2_attributes' => 'array',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean'
        ];
    }

    /**
     * The attributes that should have default values.
     *
     * @var array
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the roles associated with the user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Check if user has a specific role.
     *
     * @param string|array $role
     * @return bool
     */
    public function hasRole($role): bool
    {
        $this->loadMissing('roles');

        if (is_string($role)) {
            return $this->roles->contains('name', $role);
        }
        
        if (is_array($role)) {
            foreach ($role as $r) {
                if ($this->roles->contains('name', $r)) {
                    return true;
                }
            }
            return false;
        }
        
        return !!$role->intersect($this->roles)->count();
    }

    /**
     * Check if user has a specific permission.
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission($permission): bool
    {
        $this->loadMissing('roles.permissions');

        // Admin has all permissions
        if ($this->hasRole('admin')) {
            return true;
        }
        
        foreach ($this->roles as $role) {
            if ($role->permissions->contains('name', $permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has any of the given permissions.
     *
     * @param array $permissions
     * @return bool
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given permissions.
     *
     * @param array $permissions
     * @return bool
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Assign a role to the user.
     *
     * @param string|Role $role
     * @return void
     */
    public function assignRole($role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }
        $this->roles()->syncWithoutDetaching($role);
    }

    /**
     * Remove a role from the user.
     *
     * @param string|Role $role
     * @return void
     */
    public function removeRole($role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }
        $this->roles()->detach($role);
    }

    /**
     * Sync user roles (replace all existing roles).
     *
     * @param array $roles
     * @return void
     */
    public function syncRoles(array $roles): void
    {
        $this->roles()->sync($roles);
    }

    /**
     * Get user's role names as an array.
     *
     * @return array
     */
    public function getRoleNames(): array
    {
        return $this->roles->pluck('name')->toArray();
    }

    /**
     * Get user's role display names as an array.
     *
     * @return array
     */
    public function getRoleDisplayNames(): array
    {
        return $this->roles->pluck('display_name')->toArray();
    }

    /**
     * Check if user account is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->wso2_id !== null;
    }

    /**
     * Activate user account.
     *
     * @return void
     */
    public function activate(): void
    {
        $this->is_active = true;
        $this->save();
    }

    /**
     * Deactivate user account.
     *
     * @return void
     */
    public function deactivate(): void
    {
        $this->is_active = false;
        $this->save();
    }

    /**
     * Update last login timestamp.
     *
     * @return void
     */
    public function updateLastLogin(): void
    {
        $this->last_login_at = now();
        $this->save();
    }

    /**
     * Get the business registrations associated with the user.
     */
    public function businessRegistrations()
    {
        return $this->hasMany(BusinessRegistration::class);
    }

    /**
     * Scope a query to only include active users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query to only include users with WSO2 ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWSO2Users($query)
    {
        return $query->whereNotNull('wso2_id');
    }

    /**
     * Get the user's full name (first name + last name).
     * Assumes name field contains full name.
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get the user's initials.
     *
     * @return string
     */
    public function getInitialsAttribute(): string
    {
        $name = trim($this->name ?: $this->username ?: $this->email ?: 'User');
        $words = preg_split('/\s+/', $name) ?: [];
        $initials = collect($words)
            ->filter()
            ->take(2)
            ->map(fn ($word) => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');

        if ($initials !== '') {
            return $initials;
        }

        return Str::upper(Str::substr($name, 0, 2));
    }

    public function getAvatarUrlAttribute(): ?string
    {
        $attributes = $this->wso2_attributes ?? [];
        $avatar = $attributes['picture']
            ?? $attributes['avatar']
            ?? $attributes['photo']
            ?? $attributes['profile_picture']
            ?? $attributes['profilePhoto']
            ?? $attributes['thumbnail']
            ?? null;

        if (!$avatar) {
            return null;
        }

        if (filter_var($avatar, FILTER_VALIDATE_URL)) {
            return $avatar;
        }

        if (Str::startsWith($avatar, ['/images/', 'images/', '/storage/', 'storage/'])) {
            return asset(ltrim($avatar, '/'));
        }

        return Storage::url($avatar);
    }

    /**
     * Check if user is from WSO2 (has WSO2 ID).
     *
     * @return bool
     */
    public function isWSO2User(): bool
    {
        return !is_null($this->wso2_id);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isDigitalServices(): bool
    {
        return $this->hasRole(['digital_services', 'admin']);
    }
}
