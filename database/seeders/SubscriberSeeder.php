<?php

namespace Database\Seeders;

use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Database\Seeder;

class SubscriberSeeder extends Seeder
{
    public function run(): void
    {
        $subscriber = Subscriber::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'                   => 'Default Company',
                'plan'                   => 'premium',
                'status'                 => 'active',
                'is_active'              => true,
                'subscription_start_date' => now()->toDateString(),
                'subscription_end_date'   => now()->addYears(10)->toDateString(),
            ]
        );

        // Link the admin user to this subscriber
        User::where('email', 'admin@example.com')->update([
            'subscriber_id'       => $subscriber->id,
            'is_subscriber_admin' => true,
        ]);

        $this->command->info('Default subscriber created and linked to admin@example.com');
    }
}
