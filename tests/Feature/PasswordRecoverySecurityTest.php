<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordRecoverySecurityTest extends TestCase
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
            'first_name' => 'Recovery',
            'last_name' => 'Tester',
            'email' => 'recovery.tester@example.test',
            'email_verified_at' => now(),
            'role_id' => $role->id,
            'password' => Hash::make('OldSecure1!'),
        ]);
    }

    public function test_forgot_password_does_not_reveal_whether_an_account_exists(): void
    {
        Notification::fake();

        $existing = $this->postJson('/api/forgot-password', [
            'email' => $this->user->email,
        ]);
        $unknown = $this->postJson('/api/forgot-password', [
            'email' => 'unknown@example.test',
        ]);

        $existing->assertOk();
        $unknown->assertOk();
        $this->assertSame($existing->json(), $unknown->json());
        Notification::assertSentToTimes($this->user, ResetPassword::class, 1);
    }

    public function test_forgot_password_is_rate_limited(): void
    {
        Notification::fake();

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->postJson('/api/forgot-password', [
                'email' => $this->user->email,
            ])->assertOk();
        }

        $this->postJson('/api/forgot-password', [
            'email' => $this->user->email,
        ])->assertTooManyRequests();
    }

    public function test_mail_delivery_failure_does_not_change_the_public_response(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andThrow(new \RuntimeException('Mail transport unavailable'));

        $this->postJson('/api/forgot-password', [
            'email' => $this->user->email,
        ])
            ->assertOk()
            ->assertExactJson([
                'message' => 'If an account exists for that email, a password reset link has been sent.',
            ]);
    }

    public function test_api_reset_rejects_a_six_character_password_as_json(): void
    {
        $this->postJson('/api/reset-password', [
            'email' => $this->user->email,
            'token' => 'invalid-token',
            'password' => 'Aa1!bc',
            'password_confirmation' => 'Aa1!bc',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password')
            ->assertHeader('content-type', 'application/json');
    }

    public function test_invalid_token_and_unknown_email_return_the_same_json_contract(): void
    {
        $payload = [
            'token' => 'invalid-token',
            'password' => 'NewSecure1!',
            'password_confirmation' => 'NewSecure1!',
        ];

        $invalidToken = $this->postJson('/api/reset-password', [
            ...$payload,
            'email' => $this->user->email,
        ]);
        $unknownEmail = $this->postJson('/api/reset-password', [
            ...$payload,
            'email' => 'unknown@example.test',
        ]);

        $invalidToken->assertUnprocessable();
        $unknownEmail->assertUnprocessable();
        $this->assertSame($invalidToken->json(), $unknownEmail->json());
        $invalidToken->assertJsonPath('errors.token.0', 'The password reset token is invalid or expired.');
    }

    public function test_valid_api_reset_returns_json_updates_password_and_revokes_tokens(): void
    {
        $token = Password::createToken($this->user);
        $this->user->createToken('existing-session');

        $this->postJson('/api/reset-password', [
            'email' => $this->user->email,
            'token' => $token,
            'password' => 'NewSecure1!',
            'password_confirmation' => 'NewSecure1!',
        ])
            ->assertOk()
            ->assertExactJson(['message' => 'Password reset successfully.'])
            ->assertHeader('content-type', 'application/json');

        $this->assertTrue(Hash::check('NewSecure1!', $this->user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
