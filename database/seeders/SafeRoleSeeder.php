<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class SafeRoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view'             => 'View',
            'create'           => 'Create',
            'edit'             => 'Edit',
            'delete'           => 'Delete',
            'approval_rights'  => 'Approval Rights',
            'pricing_access'   => 'Pricing Access',
            'reports_access'   => 'Reports Access',
        ];

        foreach ($permissions as $slug => $name) {
            Permission::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        $roles = [
            'admin'       => ['view','create','edit','delete','approval_rights','pricing_access','reports_access'],
            'salesperson' => ['view','create','pricing_access'],
            'store_person' => ['view','reports_access'],
            'staff'       => ['view'],
        ];

        foreach ($roles as $slug => $rolePermissions) {
            $role = Role::firstOrCreate(
                ['slug' => $slug],
                ['name' => ucfirst(str_replace('_', ' ', $slug))]
            );

            $permissionIds = Permission::whereIn('slug', $rolePermissions)->pluck('id');
            $existing = $role->permissions()->pluck('permissions.id');
            $toAttach = $permissionIds->diff($existing);
            if ($toAttach->isNotEmpty()) {
                $role->permissions()->attach($toAttach);
            }
        }

        $this->command->info('Roles and permissions created/verified successfully.');
    }
}
