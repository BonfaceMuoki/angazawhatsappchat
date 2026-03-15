<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    public const CACHE_TTL_SECONDS = 3600; // 1 hour

    public static function cacheKey(int $userId): string
    {
        return 'user_permissions_' . $userId;
    }

    /**
     * @return array{roles: list<string>, permissions: array<string, true>}
     */
    public function getCachedForUser(int $userId): array
    {
        return Cache::remember(
            self::cacheKey($userId),
            self::CACHE_TTL_SECONDS,
            fn () => $this->loadForUser($userId)
        );
    }

    /**
     * @return array{roles: list<string>, permissions: array<string, true>}
     */
    public function loadForUser(int $userId): array
    {
        $user = User::with(['roles.permissions', 'permissions'])->find($userId);
        if (!$user) {
            return ['roles' => [], 'permissions' => []];
        }

        $roleNames = [];
        $permissions = [];

        foreach ($user->roles as $role) {
            $roleNames[] = $role->role_name;
            foreach ($role->permissions as $perm) {
                $permissions[$perm->permission_name] = true;
            }
        }
        foreach ($user->permissions as $perm) {
            $permissions[$perm->permission_name] = true;
        }

        return [
            'roles' => array_values(array_unique($roleNames)),
            'permissions' => $permissions,
        ];
    }

    public function invalidateForUser(int $userId): void
    {
        Cache::forget(self::cacheKey($userId));
    }

    public function userHasPermission(int $userId, string $permission): bool
    {
        $data = $this->getCachedForUser($userId);
        if (isset($data['permissions'][$permission])) {
            return true;
        }
        return false;
    }

    public function userIsSuperAdmin(int $userId): bool
    {
        $data = $this->getCachedForUser($userId);
        return in_array('super_admin', $data['roles'], true);
    }
}
