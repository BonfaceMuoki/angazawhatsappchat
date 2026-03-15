<?php

namespace App\Services;

use App\Jobs\SendOTPEmailJob;
use App\Jobs\SendPasswordResetEmailJob;
use App\Models\OtpCode;
use App\Models\Password;
use App\Models\PasswordReset;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public const OTP_EXPIRY_MINUTES = 5;
    public const PASSWORD_RESET_EXPIRY_MINUTES = 60;
    public const INVITE_EXPIRY_DAYS = 7;

    public function __construct(
        private JwtService $jwtService,
        private PermissionService $permissionService
    ) {}

    /**
     * Login: validate, verify password, check status, create OTP, dispatch email, return message.
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }

        $activePassword = $user->passwords()->where('status', Password::STATUS_ACTIVE)->latest()->first();
        $passwordValid = $activePassword && Hash::check($password, $activePassword->hashed_password);
        if (!$passwordValid && $user->password && Hash::check($password, $user->password)) {
            $passwordValid = true; // legacy users table password
        }
        if (!$passwordValid) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            throw ValidationException::withMessages(['email' => ['This account is blocked.']]);
        }

        $code = (string) random_int(100000, 999999);
        OtpCode::create([
            'user_id' => $user->id,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
        ]);

        SendOTPEmailJob::dispatch($user, $code);

        return ['message' => 'OTP sent to your email. Please verify.'];
    }

    /**
     * Verify OTP and return JWT payload + token.
     */
    public function verifyOtp(string $email, string $code): array
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            throw ValidationException::withMessages(['email' => ['Invalid request.']]);
        }

        $otp = $user->otpCodes()->where('used', false)->where('expires_at', '>', now())->latest()->first();
        if (!$otp || !Hash::check($code, $otp->code)) {
            throw ValidationException::withMessages(['code' => ['Invalid or expired OTP.']]);
        }

        $otp->update(['used' => true]);

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()?->ip(),
        ]);

        $token = $this->jwtService->fromUser($user);
        return [
            'message' => 'Authenticated.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $this->permissionService->getCachedForUser($user->id)['roles'],
                'permissions' => array_keys($this->permissionService->getCachedForUser($user->id)['permissions']),
            ],
        ];
    }

    public function requestPasswordReset(string $email): array
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            return ['message' => 'If the email exists, a reset link has been sent.'];
        }

        $token = bin2hex(random_bytes(32));
        PasswordReset::create([
            'user_id' => $user->id,
            'token' => Hash::make($token),
            'expires_at' => now()->addMinutes(self::PASSWORD_RESET_EXPIRY_MINUTES),
        ]);

        SendPasswordResetEmailJob::dispatch($user, $token);

        return ['message' => 'If the email exists, a reset link has been sent.'];
    }

    public function resetPassword(string $token, string $password): array
    {
        $resets = PasswordReset::where('expires_at', '>', now())->get();
        foreach ($resets as $reset) {
            if (Hash::check($token, $reset->token)) {
                $user = $reset->user;
                $user->passwords()->update(['status' => Password::STATUS_REVOKED]);
                $user->passwords()->create([
                    'hashed_password' => Hash::make($password),
                    'status' => Password::STATUS_ACTIVE,
                ]);
                $reset->delete();
                return ['message' => 'Password has been reset.'];
            }
        }
        throw ValidationException::withMessages(['token' => ['Invalid or expired reset token.']]);
    }

    public function verifyEmail(string $token): array
    {
        $userId = cache('email_verify:' . $token);
        if (!$userId) {
            throw ValidationException::withMessages(['token' => ['Invalid or expired verification token.']]);
        }
        $user = User::find($userId);
        if (!$user) {
            throw ValidationException::withMessages(['token' => ['Invalid verification.']]);
        }
        $user->update(['email_verified_at' => now()]);
        cache()->forget('email_verify:' . $token);
        return ['message' => 'Email verified.'];
    }

    public function storeEmailVerificationToken(User $user): string
    {
        $token = bin2hex(random_bytes(32));
        cache()->put('email_verify:' . $token, $user->id, now()->addDay());
        return $token;
    }

    public function acceptInvite(string $token, string $name, string $password): array
    {
        $invitation = UserInvitation::where('token', $token)->where('expires_at', '>', now())->first();
        if (!$invitation) {
            throw ValidationException::withMessages(['token' => ['Invalid or expired invitation.']]);
        }

        $user = User::create([
            'name' => $name,
            'email' => $invitation->email,
            'password' => Hash::make($password),
            'status' => User::STATUS_ACTIVE,
        ]);

        $user->passwords()->create([
            'hashed_password' => Hash::make($password),
            'status' => Password::STATUS_ACTIVE,
        ]);

        $user->roles()->attach($invitation->role_id);
        $this->permissionService->invalidateForUser($user->id);

        $invitation->delete();

        $jwtToken = $this->jwtService->fromUser($user);
        return [
            'message' => 'Invitation accepted.',
            'token' => $jwtToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ];
    }
}
