<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['viewer', 'contributor', 'developer', 'member'] as $index => $name) {
            Role::create([
                'name' => $name,
                'display_name' => ucfirst($name),
                'level' => ($index + 1) * 10,
                'permissions' => [],
            ]);
        }
    }

    public function test_registration_rejects_a_six_character_password(): void
    {
        $this->postJson('/api/register', $this->registrationData([
            'password' => 'Aa1!bc',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'new.user@example.test']);
    }

    public function test_registration_creates_an_unverified_user_without_an_api_token(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/register', $this->registrationData());

        $response
            ->assertCreated()
            ->assertJsonPath('user.email', 'new.user@example.test')
            ->assertJsonPath('user.email_verified_at', null)
            ->assertJsonMissingPath('token');

        $user = User::where('email', 'new.user@example.test')->firstOrFail();

        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertDatabaseCount('personal_access_tokens', 0);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_unverified_user_cannot_receive_a_token_by_logging_in(): void
    {
        Notification::fake();

        $this->postJson('/api/register', $this->registrationData())->assertCreated();

        $this->postJson('/api/login', [
            'email' => 'new.user@example.test',
            'password' => 'SecurePass1!',
        ])
            ->assertForbidden()
            ->assertJsonPath('reason', 'email_unverified')
            ->assertJsonMissingPath('token');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_verified_user_receives_a_token_when_logging_in(): void
    {
        Notification::fake();

        $this->postJson('/api/register', $this->registrationData())->assertCreated();

        User::where('email', 'new.user@example.test')
            ->firstOrFail()
            ->markEmailAsVerified();

        $this->postJson('/api/login', [
            'email' => 'new.user@example.test',
            'password' => 'SecurePass1!',
        ])
            ->assertOk()
            ->assertJsonStructure(['token'])
            ->assertJsonPath('user.email_verified_at', fn ($value) => $value !== null);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_registration_is_rate_limited_after_five_attempts_per_ip(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/register', $this->registrationData([
                'email' => "attempt{$attempt}@example.test",
                'password' => 'weak',
            ]))->assertUnprocessable();
        }

        $this->postJson('/api/register', $this->registrationData([
            'email' => 'attempt6@example.test',
            'password' => 'weak',
        ]))->assertTooManyRequests();
    }

    private function registrationData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'New',
            'last_name' => 'User',
            'email' => 'new.user@example.test',
            'password' => 'SecurePass1!',
            'password_confirmation' => 'SecurePass1!',
        ], $overrides);
    }
}
