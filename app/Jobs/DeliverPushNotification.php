<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeliverPushNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $userId, public readonly array $payload)
    {
        $this->afterCommit();
    }

    public function handle(WebPushService $push): void
    {
        $user = User::find($this->userId);
        if ($user) {
            $push->send($user, $this->payload);
        }
    }
}
