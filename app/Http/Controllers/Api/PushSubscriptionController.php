<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    /**
     * Return the VAPID public key for the client to subscribe.
     */
    public function vapidPublic(): JsonResponse
    {
        $key = config('services.webpush.vapid_public');
        return response()->json(['public_key' => $key ?: '']);
    }

    /**
     * Store a push subscription (endpoint + keys).
     */
    public function store(Request $request): JsonResponse
    {
        $endpoint = $request->input('endpoint');
        $keys = $request->input('keys');
        if (!$endpoint || !is_array($keys)) {
            return response()->json(['message' => 'endpoint and keys (p256dh, auth) required'], 422);
        }

        $p256dh = $keys['p256dh'] ?? null;
        $auth = $keys['auth'] ?? null;
        if (!$p256dh || !$auth) {
            return response()->json(['message' => 'keys.p256dh and keys.auth required'], 422);
        }

        PushSubscription::updateOrCreate(
            ['endpoint' => $endpoint],
            ['public_key' => $p256dh, 'auth_token' => $auth]
        );

        return response()->json(['ok' => true], 201);
    }

    /**
     * Remove a push subscription by endpoint.
     */
    public function destroy(Request $request): JsonResponse
    {
        $endpoint = $request->input('endpoint');
        if (!$endpoint) {
            return response()->json(['message' => 'endpoint required'], 422);
        }
        PushSubscription::where('endpoint', $endpoint)->delete();
        return response()->json(['ok' => true]);
    }
}
