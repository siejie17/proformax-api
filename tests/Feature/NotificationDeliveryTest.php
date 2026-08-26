<?php

namespace Tests\Feature;

use App\Jobs\DeliverPushNotification;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ProjectActivityEmail;
use App\Services\NotificationDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationDeliveryTest extends TestCase
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
            'first_name' => 'Notify',
            'last_name' => 'Tester',
            'email' => 'notify@example.test',
            'password' => 'password',
            'role_id' => $role->id,
            'email_notifications' => true,
            'push_notifications' => false,
        ]);
    }

    public function test_email_and_push_preferences_gate_real_delivery_channels(): void
    {
        Notification::fake();
        Bus::fake();
        $delivery = app(NotificationDeliveryService::class);

        $delivery->deliver($this->user, 'Assessment verified', 'Your assessment was verified.', '/projects/1');

        Notification::assertSentTo($this->user, ProjectActivityEmail::class);
        Bus::assertNotDispatched(DeliverPushNotification::class);

        Notification::fake();
        $this->user->update(['email_notifications' => false, 'push_notifications' => true]);
        $delivery->deliver($this->user->fresh(), 'New message', 'A teammate sent a message.', '/projects/1', false);

        Notification::assertNothingSent();
        Bus::assertDispatched(DeliverPushNotification::class, fn ($job) => $job->userId === $this->user->id
            && $job->payload['title'] === 'New message');
    }

    public function test_user_can_register_and_remove_only_their_own_device_subscription(): void
    {
        config()->set('services.web_push.public_key', 'public-key');
        config()->set('services.web_push.private_key', 'private-key');
        Sanctum::actingAs($this->user);

        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/subscriptions/device-one',
            'keys' => ['p256dh' => 'device-public-key', 'auth' => 'device-auth-token'],
            'content_encoding' => 'aes128gcm',
        ];

        $this->postJson('/api/push/subscriptions', $payload)
            ->assertCreated();

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->user->id,
            'endpoint_hash' => hash('sha256', $payload['endpoint']),
        ]);

        $this->deleteJson('/api/push/subscriptions', ['endpoint' => $payload['endpoint']])
            ->assertOk();
        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_arbitrary_push_endpoint_is_rejected(): void
    {
        config()->set('services.web_push.public_key', 'public-key');
        config()->set('services.web_push.private_key', 'private-key');
        Sanctum::actingAs($this->user);

        $this->postJson('/api/push/subscriptions', [
            'endpoint' => 'https://attacker.example.test/callback',
            'keys' => ['p256dh' => 'device-public-key', 'auth' => 'device-auth-token'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');
    }

    public function test_push_configuration_reports_device_readiness(): void
    {
        config()->set('services.web_push.public_key', 'browser-public-key');
        config()->set('services.web_push.private_key', 'server-private-key');
        Sanctum::actingAs($this->user);

        $this->getJson('/api/push/config')
            ->assertOk()
            ->assertJson([
                'enabled' => true,
                'public_key' => 'browser-public-key',
                'subscribed' => false,
            ]);
    }
}
