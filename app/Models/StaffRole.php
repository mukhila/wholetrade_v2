<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffRole extends Model
{
    protected $fillable = ['subscriber_id', 'name', 'description'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function modulePermissions()
    {
        return $this->hasMany(StaffRolePermission::class);
    }

    public function subscriber()
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function hasPermission(string $module, string $action): bool
    {
        $perm = $this->modulePermissions->where('module', $module)->first();
        if (! $perm) return false;
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
