<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'subscriber_id',
        'is_super_admin',
        'is_subscriber_admin',
        'is_active',
        'staff_role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'password'            => 'hashed',
            'is_super_admin'      => 'boolean',
            'is_subscriber_admin' => 'boolean',
            'is_active'           => 'boolean',
        ];
    }

    public function subscriber()
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    public function isSubscriberAdmin(): bool
    {
        return $this->is_subscriber_admin === true;
    }

    public function hasActiveSubscription(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->subscriber && $this->subscriber->isActive();
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles->contains('slug', $role);
        }
        return !! $role->intersect($this->roles)->count();
    }

    public function permissions()
    {
        return $this->roles->map->permissions->flatten()->pluck('name')->unique();
    }

    public function staffPermissions()
    {
        return $this->hasMany(StaffPermission::class);
    }

    public function staffRole()
    {
        return $this->belongsTo(StaffRole::class);
    }

    public function canViewModule(string $module): bool
    {
        $module = app(\App\Services\PermissionService::class)->normalizeModule($module);

        if ($this->isSuperAdmin() || $this->hasRole('admin')) {
            return true;
        }
        if ($this->hasRole('staff')) {
            return $this->hasStaffPermission($module, 'view');
        }
        return false;
    }

    public function hasStaffPermission(string $module, string $action): bool
    {
        $module = app(\App\Services\PermissionService::class)->normalizeModule($module);

        if ($this->isSuperAdmin() || $this->hasRole('admin')) {
            return true;
        }

        if ($this->staff_role_id) {
            $this->loadMissing('staffRole.modulePermissions');
            if ($this->staffRole && $this->staffRole->hasPermission($module, $action)) {
                return true;
            }
        }

        $perm = $this->staffPermissions->where('module', $module)->first();
        if (! $perm) {
            return false;
        }
        return match ($action) {
            'view'   => $perm->can_view,
            'create' => $perm->can_create,
            'edit'   => $perm->can_edit,
            'delete' => $perm->can_delete,
            'export' => $perm->can_export ?? false,
            'import' => $perm->can_import ?? false,
            default  => false,
        };
    }
}
