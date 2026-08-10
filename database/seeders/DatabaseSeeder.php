<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LocationSeeder::class,
            StructureSeeder::class,
            RoleSeeder::class,
        ]);

        // User::factory(10)->create();

        $defaultRoleId = Role::where('name', 'member')->value('id');

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role_id' => $defaultRoleId,
        ]);
    }
}
