<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    public function send(User $user, array $payload): void
    {
        if (! $user->push_notifications) {
            return;
        }

        $config = config('services.web_push');
        if (! filled($config['public_key'] ?? null) || ! filled($config['private_key'] ?? null)) {
            Log::warning('Web Push delivery skipped because VAPID is not configured.');

            return;
        }

        $subscriptions = $user->pushSubscriptions()->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $webPush = new WebPush(['VAPID' => [
            'subject' => $config['subject'],
            'publicKey' => $config['public_key'],
            'privateKey' => $config['private_key'],
        ]], ['TTL' => 300, 'urgency' => 'normal']);

        $encodedPayload = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        foreach ($subscriptions as $stored) {
            $webPush->queueNotification(new Subscription(
                $stored->endpoint,
                $stored->public_key,
                $stored->auth_token,
                $stored->content_encoding,
            ), $encodedPayload);
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                $user->pushSubscriptions()->where('endpoint_hash', hash('sha256', $report->getEndpoint()))->delete();
            } elseif (! $report->isSuccess()) {
                Log::warning('Web Push delivery failed.', ['reason' => $report->getReason()]);
            }
        }
    }
}
