<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;

class RolePolicy
{
    public function __construct(private PermissionService $permissionService) {}

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $this->permissionService->userHasPermission($user->id, 'roles.assign');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $this->permissionService->userHasPermission($user->id, 'roles.assign');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->isSuperAdmin() || $this->permissionService->userHasPermission($user->id, 'roles.assign');
    }

    public function assignPermissions(User $user, Role $role): bool
    {
        return $user->isSuperAdmin() || $this->permissionService->userHasPermission($user->id, 'permissions.assign');
    }
}
