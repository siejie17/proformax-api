<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name'         => 'member',
                'display_name' => 'Member',
                'description'  => 'Can view messages and see project members.',
                'level'        => 10,
                'permissions'  => ['view_messages', 'view_members'],
            ],
            [
                'name'         => 'developer',
                'display_name' => 'Developer',
                'description'  => 'Can send messages, upload attachments, and view project data.',
                'level'        => 20,
                'permissions'  => ['view_messages', 'view_members', 'send_messages', 'upload_attachments'],
            ],
            [
                'name'         => 'quantity_surveyor',
                'display_name' => 'Quantity Surveyor',
                'description'  => 'Can manage project members and perform cost-related actions.',
                'level'        => 30,
                'permissions'  => ['view_messages', 'view_members', 'send_messages', 'upload_attachments', 'manage_members'],
            ],
            [
                'name'         => 'gbi_facilitator',
                'display_name' => 'GBI Facilitator',
                'description'  => 'Full administrative access to the project including role management.',
                'level'        => 40,
                'permissions'  => ['view_messages', 'view_members', 'send_messages', 'upload_attachments', 'manage_members', 'manage_roles', 'admin'],
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role,
            );
        }
    }
}
