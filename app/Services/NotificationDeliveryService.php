<?php

namespace App\Services;

use App\Jobs\DeliverPushNotification;
use App\Models\User;
use App\Notifications\ProjectActivityEmail;
use Illuminate\Support\Facades\DB;

class NotificationDeliveryService
{
    public function deliver(
        User $user,
        string $title,
        string $message,
        string $url,
        bool $email = true,
        ?User $actor = null,
        ?int $projectId = null,
    ): void {
        DB::table('notifications')->insert([
            'user_id' => $user->id,
            'actor_user_id' => $actor?->id,
            'project_id' => $projectId,
            'type' => 'project_activity',
            'title' => $title,
            'message' => $message,
            'metadata' => json_encode(['url' => $url]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($email && $user->email_notifications) {
            $user->notify(new ProjectActivityEmail($title, $message, $url));
        }

        if ($user->push_notifications) {
            DeliverPushNotification::dispatch($user->id, compact('title', 'message', 'url'));
        }
    }
}
