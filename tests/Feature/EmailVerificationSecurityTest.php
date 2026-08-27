<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationSecurityTest extends TestCase
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
            'first_name' => 'Verify',
            'last_name' => 'Tester',
            'email' => 'verify.tester@example.test',
            'role_id' => $role->id,
            'password' => Hash::make('SecurePass1!'),
        ]);
    }

    public function test_predictable_email_hash_without_a_signature_cannot_verify_an_account(): void
    {
        $this->get(route('verification.verify', [
            'id' => $this->user->id,
            'hash' => sha1($this->user->getEmailForVerification()),
        ]))->assertForbidden();

        $this->assertFalse($this->user->fresh()->hasVerifiedEmail());
    }

    public function test_valid_signed_link_verifies_an_account(): void
    {
        $this->get($this->signedVerificationUrl())
            ->assertOk()
            ->assertSee('Email Verified!');

        $this->assertTrue($this->user->fresh()->hasVerifiedEmail());
    }

    public function test_tampering_with_a_signed_link_cannot_verify_an_account(): void
    {
        $validHash = sha1($this->user->getEmailForVerification());
        $tamperedUrl = str_replace($validHash, sha1('attacker@example.test'), $this->signedVerificationUrl());

        $this->get($tamperedUrl)->assertForbidden();

        $this->assertFalse($this->user->fresh()->hasVerifiedEmail());
    }

    public function test_public_api_resend_is_safe_and_does_not_reveal_account_state(): void
    {
        Notification::fake();

        $unverified = $this->postJson('/api/email/verification-notification', [
            'email' => $this->user->email,
        ]);

        $this->user->markEmailAsVerified();

        $verified = $this->postJson('/api/email/verification-notification', [
            'email' => $this->user->email,
        ]);
        $unknown = $this->postJson('/api/email/verification-notification', [
            'email' => 'unknown@example.test',
        ]);

        $unverified->assertOk();
        $verified->assertOk();
        $unknown->assertOk();
        $this->assertSame($unverified->json(), $verified->json());
        $this->assertSame($unverified->json(), $unknown->json());
        Notification::assertSentToTimes($this->user, VerifyEmail::class, 1);
    }

    public function test_verification_resend_is_rate_limited(): void
    {
        Notification::fake();

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->postJson('/api/email/verification-notification', [
                'email' => $this->user->email,
            ])->assertOk();
        }

        $this->postJson('/api/email/verification-notification', [
            'email' => $this->user->email,
        ])->assertTooManyRequests();
    }

    private function signedVerificationUrl(): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $this->user->id,
                'hash' => sha1($this->user->getEmailForVerification()),
            ]
        );
    }
}
