<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;

class AssignUserRole extends Command
{
    protected $signature = 'roles:assign {email : The user email address} {role : Role slug (admin, salesperson, store_person, staff)}';

    protected $description = 'Assign a role to an existing user by email';

    public function handle(): int
    {
        $email = $this->argument('email');
        $roleSlug = $this->argument('role');

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("No user found with email: {$email}");
            return 1;
        }

        $role = Role::where('slug', $roleSlug)->first();
        if (! $role) {
            $this->error("No role found with slug: {$roleSlug}");
            $this->line('Available roles: ' . Role::pluck('slug')->join(', '));
            return 1;
        }

        if ($user->roles()->where('roles.id', $role->id)->exists()) {
            $this->info("User [{$email}] already has the [{$roleSlug}] role.");
            return 0;
        }

        $user->roles()->attach($role->id);
        $this->info("Role [{$roleSlug}] assigned to [{$email}] successfully.");
        return 0;
    }
}
