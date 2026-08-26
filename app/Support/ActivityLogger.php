<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public static function record(?User $user, string $action, Model|string|null $target = null, string $status = 'success', array $metadata = []): void
    {
        ActivityLog::create([
            'user_id' => $user?->id,
            'system_role' => $user?->system_role,
            'action' => $action,
            'target_type' => $target instanceof Model ? $target->getMorphClass() : null,
            'target_id' => $target instanceof Model ? $target->getKey() : null,
            'target_label' => is_string($target) ? $target : self::label($target),
            'status' => $status,
            'metadata' => $metadata ?: null,
        ]);
    }

    private static function label(Model|null $target): ?string
    {
        if (! $target) return null;

        return $target->name ?? $target->title ?? $target->email ?? class_basename($target) . ' #' . $target->getKey();
    }
}
