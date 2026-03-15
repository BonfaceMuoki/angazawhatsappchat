<?php

namespace App\Policies;

use App\Models\User;
use App\Services\PermissionService;

class UserPolicy
{
    public function __construct(private PermissionService $permissionService) {}

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $this->permissionService->userHasPermission($user->id, 'users.update');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $this->permissionService->userHasPermission($user->id, 'users.create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->isSuperAdmin() || $this->permissionService->userHasPermission($user->id, 'users.update');
    }

    public function blockUser(User $user, User $target): bool
    {
        return $user->isSuperAdmin() || $this->permissionService->userHasPermission($user->id, 'users.block');
    }

    public function assignRole(User $user, User $target): bool
    {
        return $user->isSuperAdmin() || $this->permissionService->userHasPermission($user->id, 'roles.assign');
    }

    public function assignPermission(User $user, User $target): bool
    {
        return $user->isSuperAdmin() || $this->permissionService->userHasPermission($user->id, 'permissions.assign');
    }
}
