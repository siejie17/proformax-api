<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
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
            'first_name' => 'Login',
            'last_name' => 'Tester',
            'email' => 'login.tester@example.test',
            'email_verified_at' => now(),
            'role_id' => $role->id,
            'password' => Hash::make('SecurePass1!'),
        ]);
    }

    public function test_unknown_email_and_wrong_password_return_the_same_response(): void
    {
        $unknownEmail = $this->postJson('/api/login', [
            'email' => 'unknown@example.test',
            'password' => 'SecurePass1!',
        ]);

        $wrongPassword = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => 'WrongPass1!',
        ]);

        $unknownEmail->assertUnauthorized();
        $wrongPassword->assertUnauthorized();
        $this->assertSame($unknownEmail->json(), $wrongPassword->json());
        $this->assertSame(['message' => 'Invalid credentials.'], $unknownEmail->json());
    }

    public function test_login_is_rate_limited_after_five_attempts_for_an_email_and_ip(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/login', [
                'email' => $this->user->email,
                'password' => 'WrongPass1!',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => 'WrongPass1!',
        ])->assertTooManyRequests();
    }

    public function test_login_is_rate_limited_across_different_emails_from_one_ip(): void
    {
        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $this->postJson('/api/login', [
                'email' => "unknown{$attempt}@example.test",
                'password' => 'WrongPass1!',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/login', [
            'email' => 'unknown21@example.test',
            'password' => 'WrongPass1!',
        ])->assertTooManyRequests();
    }
}
