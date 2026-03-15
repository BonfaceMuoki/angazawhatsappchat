<?php

namespace App\Auth;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class JwtGuard implements Guard
{
    public function __construct(
        private Request $request,
        private JwtService $jwt
    ) {}

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function hasUser(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?User
    {
        if (isset($this->request->attributes->getIterator()['auth_user'])) {
            return $this->request->attributes->get('auth_user');
        }

        $header = $this->request->header('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $payload = $this->jwt->decode(substr($header, 7));
        if (!$payload || empty($payload['user_id'])) {
            return null;
        }

        $user = User::find($payload['user_id']);
        if (!$user || $user->token_version !== (int) ($payload['token_version'] ?? 0)) {
            return null;
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            return null;
        }

        $this->request->attributes->set('auth_user', $user);
        return $user;
    }

    public function id(): int|string|null
    {
        $user = $this->user();
        return $user?->getAuthIdentifier();
    }

    public function validate(array $credentials = []): bool
    {
        return false;
    }

    public function setUser($user): static
    {
        $this->request->attributes->set('auth_user', $user);
        return $this;
    }
}
