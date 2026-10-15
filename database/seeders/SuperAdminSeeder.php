<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@wholetrade.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('superadmin123'),
                'is_super_admin' => true,
                'subscriber_id' => null,
            ]
        );

        $this->command->info('Super Admin created: superadmin@wholetrade.com / superadmin123');
    }
}
