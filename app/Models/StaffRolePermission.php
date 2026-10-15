<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffRolePermission extends Model
{
    protected $table = 'staff_role_module_permissions';

    protected $fillable = [
        'staff_role_id',
        'module',
        'can_view',
        'can_create',
        'can_edit',
        'can_delete',
        'can_export',
        'can_import',
    ];

    protected $casts = [
        'can_view'   => 'boolean',
        'can_create' => 'boolean',
        'can_edit'   => 'boolean',
        'can_delete' => 'boolean',
        'can_export' => 'boolean',
        'can_import' => 'boolean',
    ];

    public function staffRole()
    {
        return $this->belongsTo(StaffRole::class);
    }
}
