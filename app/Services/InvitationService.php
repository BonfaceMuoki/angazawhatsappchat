<?php

namespace App\Services;

use App\Jobs\SendInvitationEmailJob;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    public const INVITE_EXPIRY_DAYS = 7;

    public function invite(string $email, int $roleId, ?int $invitedBy = null): UserInvitation
    {
        if (User::withTrashed()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => ['A user with this email already exists.']]);
        }

        $role = Role::findOrFail($roleId);
        $token = Str::random(64);

        $invitation = UserInvitation::create([
            'email' => $email,
            'role_id' => $roleId,
            'token' => $token,
            'expires_at' => now()->addDays(self::INVITE_EXPIRY_DAYS),
            'invited_by' => $invitedBy,
        ]);

        SendInvitationEmailJob::dispatch($invitation);

        return $invitation;
    }
}
