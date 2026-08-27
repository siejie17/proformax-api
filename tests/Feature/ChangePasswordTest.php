<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'name' => 'member',
            'display_name' => 'Member',
            'level' => 10,
            'permissions' => [],
        ]);

        $this->user = User::create([
            'first_name' => 'Password',
            'last_name' => 'Tester',
            'email' => 'password.tester@example.test',
            'role_id' => $role->id,
            'password' => Hash::make('current-password'),
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_current_password_is_required(): void
    {
        $this->putJson('/api/user/update-password', [
            'new_password' => 'new-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('current-password', $this->user->fresh()->password));
    }

    public function test_incorrect_current_password_is_rejected(): void
    {
        $this->putJson('/api/user/update-password', [
            'current_password' => 'wrong-password',
            'new_password' => 'new-password',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The current password is incorrect.')
            ->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('current-password', $this->user->fresh()->password));
    }

    public function test_correct_current_password_updates_password(): void
    {
        $this->putJson('/api/user/update-password', [
            'current_password' => 'current-password',
            'new_password' => 'new-password',
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Password updated successfully.',
            ]);

        $password = $this->user->fresh()->password;

        $this->assertFalse(Hash::check('current-password', $password));
        $this->assertTrue(Hash::check('new-password', $password));
    }
}
