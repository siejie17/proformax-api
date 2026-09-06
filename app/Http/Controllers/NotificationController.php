<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function unreadCount()
    {
        $count = Notification::query()
            ->where('user_id', Auth::id())
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'count' => $count,
        ]);
    }

    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);

        $notifications = Notification::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->limit(max(1, min($limit, 50)))
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => (string) $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'link' => $notification->link,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at?->toISOString(),
                ];
            });

        return response()->json([
            'data' => $notifications,
        ]);
    }

    public function markAsRead(Notification $notification)
    {
        if ((int) $notification->user_id !== (int) Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (is_null($notification->read_at)) {
            $notification->update([
                'read_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function markAllAsRead()
    {
        Notification::query()
            ->where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
        ]);
    }
}
