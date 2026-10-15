<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class StaffRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure 'staff' role exists
        Role::firstOrCreate(
            ['slug' => 'staff'],
            ['name' => 'Staff']
        );
    }
}
