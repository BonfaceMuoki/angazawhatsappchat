<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;
use App\Services\PermissionService;

class PermissionPolicy
{
    public function __construct(private PermissionService $permissionService) {}

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $this->permissionService->userHasPermission($user->id, 'permissions.assign');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $this->permissionService->userHasPermission($user->id, 'permissions.assign');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->isSuperAdmin() || $this->permissionService->userHasPermission($user->id, 'permissions.assign');
    }
}
