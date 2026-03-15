<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    public function __construct(private JwtService $jwt) {}

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $token = substr($header, 7);
        $payload = $this->jwt->decode($token);
        if (!$payload || empty($payload['user_id'])) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired token'], 401);
        }

        $user = User::find($payload['user_id']);
        if (!$user || $user->token_version !== (int) ($payload['token_version'] ?? 0)) {
            return response()->json(['success' => false, 'message' => 'Token invalidated'], 401);
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            return response()->json(['success' => false, 'message' => 'Account blocked'], 403);
        }

        $request->setUserResolver(fn () => $user);
        $request->attributes->set('jwt_payload', $payload);

        return $next($request);
    }
}
