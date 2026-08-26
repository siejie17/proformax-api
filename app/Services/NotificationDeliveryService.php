<?php

namespace App\Services;

use App\Jobs\DeliverPushNotification;
use App\Models\User;
use App\Notifications\ProjectActivityEmail;

class NotificationDeliveryService
{
    public function deliver(User $user, string $title, string $message, string $url, bool $email = true): void
    {
        if ($email && $user->email_notifications) {
            $user->notify(new ProjectActivityEmail($title, $message, $url));
        }

        if ($user->push_notifications) {
            DeliverPushNotification::dispatch($user->id, compact('title', 'message', 'url'));
        }
    }
}
