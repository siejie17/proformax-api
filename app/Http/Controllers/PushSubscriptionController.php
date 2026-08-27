<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PushSubscriptionController extends Controller
{
    public function config(Request $request): JsonResponse
    {
        $publicKey = config('services.web_push.public_key');

        return response()->json([
            'enabled' => filled($publicKey) && filled(config('services.web_push.private_key')),
            'public_key' => $publicKey,
            'subscribed' => $request->user()->pushSubscriptions()->exists(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(filled(config('services.web_push.public_key')) && filled(config('services.web_push.private_key')), 503, 'Push delivery is not configured.');

        $data = $request->validate([
            'endpoint' => ['required', 'url:https', 'max:2048'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:512'],
            'keys.auth' => ['required', 'string', 'max:512'],
            'content_encoding' => ['nullable', Rule::in(['aesgcm', 'aes128gcm'])],
        ]);

        $host = strtolower((string) parse_url($data['endpoint'], PHP_URL_HOST));
        $allowed = collect(config('services.web_push.allowed_hosts', []))
            ->contains(fn (string $allowedHost) => $host === $allowedHost || str_ends_with($host, '.'.$allowedHost));
        if (! $allowed) {
            throw ValidationException::withMessages([
                'endpoint' => ['The endpoint is not an approved browser push service.'],
            ]);
        }

        $subscription = $request->user()->pushSubscriptions()->updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'endpoint' => $data['endpoint'],
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['content_encoding'] ?? 'aes128gcm',
            ]
        );

        return response()->json(['message' => 'Push subscription saved.', 'id' => $subscription->id], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'url:https', 'max:2048']]);
        $request->user()->pushSubscriptions()
            ->where('endpoint_hash', hash('sha256', $data['endpoint']))
            ->delete();

        return response()->json(['message' => 'Push subscription removed.']);
    }
}
