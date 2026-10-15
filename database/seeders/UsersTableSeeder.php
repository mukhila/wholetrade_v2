<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subscriber = \App\Models\Subscriber::first();

        // Create Salesperson User
        $salesUser = \App\Models\User::firstOrCreate(
            ['email' => 'sales@example.com'],
            [
                'name' => 'Salesperson User',
                'password' => bcrypt('password'),
                'subscriber_id' => $subscriber?->id,
            ]
        );

        $salesRole = \App\Models\Role::where('slug', 'salesperson')->first();
        if ($salesRole && !$salesUser->roles()->where('roles.id', $salesRole->id)->exists()) {
            $salesUser->roles()->attach($salesRole);
        }

        // Create Store Person User
        $storeUser = \App\Models\User::firstOrCreate(
            ['email' => 'store@example.com'],
            [
                'name' => 'Store Person User',
                'password' => bcrypt('password'),
                'subscriber_id' => $subscriber?->id,
            ]
        );

        $storeRole = \App\Models\Role::where('slug', 'store_person')->first();
        if ($storeRole && !$storeUser->roles()->where('roles.id', $storeRole->id)->exists()) {
            $storeUser->roles()->attach($storeRole);
        }
    }
}
