<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view',
            'create',
            'edit',
            'delete',
            'approval_rights',
            'pricing_access',
            'reports_access',
        ];

        foreach ($permissions as $permission) {
            \App\Models\Permission::create([
                'name' => ucfirst(str_replace('_', ' ', $permission)),
                'slug' => $permission,
            ]);
        }

        $roles = [
            'admin' => $permissions,
            'salesperson' => ['view', 'create', 'pricing_access'],
            'store_person' => ['view', 'reports_access'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = \App\Models\Role::create([
                'name' => ucfirst(str_replace('_', ' ', $roleName)),
                'slug' => $roleName,
            ]);

            $permissionIds = \App\Models\Permission::whereIn('slug', $rolePermissions)->pluck('id');
            $role->permissions()->attach($permissionIds);
        }

        // Create Admin User
        $admin = \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $adminRole = \App\Models\Role::where('slug', 'admin')->first();
        $admin->roles()->attach($adminRole);
    }
}
