<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetSystemRole extends Command
{
    protected $signature = 'system-role:set {email} {role : user|facilitator_admin|admin|super_admin}';
    protected $description = 'Assign a platform system role without changing project collaboration roles';

    public function handle(): int
    {
        $role = (string) $this->argument('role');
        if (! in_array($role, ['user', 'facilitator_admin', 'admin', 'super_admin'], true)) {
            $this->error('Invalid system role.');
            return self::FAILURE;
        }

        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('No account matches that email.');
            return self::FAILURE;
        }

        $user->update(['system_role' => $role]);
        $this->info("{$user->email} is now {$role}.");

        return self::SUCCESS;
    }
}
