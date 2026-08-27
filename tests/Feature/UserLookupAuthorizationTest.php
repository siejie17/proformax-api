<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserLookupAuthorizationTest extends TestCase
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

        $this->user = $this->createUser($role, 'user@example.test');
        $this->otherUser = $this->createUser($role, 'other@example.test');

        Sanctum::actingAs($this->user);
    }

    public function test_user_can_only_fetch_their_own_allowlisted_profile(): void
    {
        $this->getJson("/api/users/{$this->user->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.id', (string) $this->user->id)
            ->assertJsonPath('user.email', $this->user->email)
            ->assertJsonMissingPath('user.email_verified_at')
            ->assertJsonMissingPath('user.email_notifications')
            ->assertJsonMissingPath('user.push_notifications')
            ->assertJsonMissingPath('user.system_role')
            ->assertJsonMissingPath('user.created_at')
            ->assertJsonMissingPath('user.updated_at');
    }

    public function test_user_cannot_fetch_another_users_record(): void
    {
        $this->getJson("/api/users/{$this->otherUser->id}")
            ->assertForbidden()
            ->assertJsonMissingPath('user');
    }

    public function test_legacy_lookup_has_the_same_authorization(): void
    {
        $this->getJson("/users/{$this->otherUser->id}")
            ->assertForbidden()
            ->assertJsonMissingPath('user');
    }

    public function test_unauthenticated_user_cannot_fetch_a_user_record(): void
    {
        auth()->forgetGuards();

        $this->getJson("/api/users/{$this->user->id}")
            ->assertUnauthorized()
            ->assertJsonMissingPath('user');
    }

    private function createUser(Role $role, string $email): User
    {
        return User::create([
            'first_name' => 'Lookup',
            'last_name' => 'Tester',
            'email' => $email,
            'password' => 'password',
            'role_id' => $role->id,
            'email_verified_at' => now(),
            'email_notifications' => true,
            'push_notifications' => true,
            'system_role' => 'user',
        ]);
    }
}
