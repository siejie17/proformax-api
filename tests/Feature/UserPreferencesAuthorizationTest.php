<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserPreferencesAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'name' => 'member',
            'display_name' => 'Member',
            'level' => 10,
            'permissions' => [],
        ]);

        $this->user = $this->createUser($role, 'user@example.test', true, false);
        $this->otherUser = $this->createUser($role, 'other@example.test', false, true);

        Sanctum::actingAs($this->user);
    }

    public function test_user_can_read_own_preferences(): void
    {
        $this->getJson("/api/users/{$this->user->id}/preferences")
            ->assertOk()
            ->assertJsonPath('preferences.email_notifications', true)
            ->assertJsonPath('preferences.push_notifications', false);
    }

    public function test_user_cannot_read_another_users_preferences(): void
    {
        $this->getJson("/api/users/{$this->otherUser->id}/preferences")
            ->assertForbidden()
            ->assertJsonMissingPath('preferences');
    }

    public function test_user_can_update_own_preferences(): void
    {
        $this->patchJson("/api/users/{$this->user->id}/preferences", [
            'email_notifications' => false,
            'push_notifications' => true,
        ])
            ->assertOk()
            ->assertJsonPath('preferences.email_notifications', false)
            ->assertJsonPath('preferences.push_notifications', true);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email_notifications' => false,
            'push_notifications' => true,
        ]);
    }

    public function test_user_cannot_update_another_users_preferences(): void
    {
        $this->patchJson("/api/users/{$this->otherUser->id}/preferences", [
            'email_notifications' => true,
            'push_notifications' => false,
        ])->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $this->otherUser->id,
            'email_notifications' => false,
            'push_notifications' => true,
        ]);
    }

    private function createUser(Role $role, string $email, bool $emailNotifications, bool $pushNotifications): User
    {
        return User::create([
            'first_name' => 'Preference',
            'last_name' => 'Tester',
            'email' => $email,
            'password' => 'password',
            'role_id' => $role->id,
            'email_notifications' => $emailNotifications,
            'push_notifications' => $pushNotifications,
        ]);
    }
}
