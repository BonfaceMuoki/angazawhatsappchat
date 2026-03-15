<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class JwtService
{
    private const ALG = 'HS256';
    private const TTL_MINUTES = 60 * 24 * 7; // 7 days

    public function __construct(
        private PermissionService $permissionService
    ) {}

    public function fromUser(User $user): string
    {
        $payload = $this->permissionService->getCachedForUser($user->id);
        $payload['user_id'] = $user->id;
        $payload['email'] = $user->email;
        $payload['token_version'] = $user->token_version;
        $payload['iat'] = time();
        $payload['exp'] = time() + (self::TTL_MINUTES * 60);
        return $this->encode($payload);
    }

    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        $payload = $this->base64UrlDecode($parts[1]);
        if (!$payload) {
            return null;
        }
        $data = json_decode($payload, true);
        if (!is_array($data) || empty($data['exp']) || $data['exp'] < time()) {
            return null;
        }
        $sig = $this->sign($parts[0] . '.' . $parts[1]);
        if (!hash_equals($sig, $parts[2])) {
            return null;
        }
        return $data;
    }

    public function invalidateUser(int $userId): void
    {
        $user = User::find($userId);
        if ($user) {
            $user->increment('token_version');
            Cache::forget(PermissionService::cacheKey($userId));
        }
    }

    private function encode(array $payload): string
    {
        $header = ['alg' => self::ALG, 'typ' => 'JWT'];
        $seg1 = $this->base64UrlEncode(json_encode($header));
        $seg2 = $this->base64UrlEncode(json_encode($payload));
        $sig = $this->sign($seg1 . '.' . $seg2);
        return $seg1 . '.' . $seg2 . '.' . $sig;
    }

    private function sign(string $input): string
    {
        $secret = config('auth.jwt_secret') ?: config('app.key');
        $raw = hash_hmac('sha256', $input, $secret, true);
        return $this->base64UrlEncode($raw);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string|false
    {
        $pad = strlen($data) % 4;
        if ($pad) {
            $data .= str_repeat('=', 4 - $pad);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
