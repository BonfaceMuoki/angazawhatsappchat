<?php

namespace App\Services;

use App\Models\Password;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    public function __construct(
        private PermissionService $permissionService,
        private JwtService $jwtService
    ) {}

    public function createUser(string $name, string $email, string $password, array $roleIds = []): User
    {
        if (User::withTrashed()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => ['The email has already been taken.']]);
        }
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'status' => User::STATUS_ACTIVE,
        ]);
        $user->passwords()->create([
            'hashed_password' => Hash::make($password),
            'status' => Password::STATUS_ACTIVE,
        ]);
        if (!empty($roleIds)) {
            $user->roles()->attach($roleIds);
            $this->permissionService->invalidateForUser($user->id);
        }
        return $user;
    }

    public function listUsers(int $perPage = 15): LengthAwarePaginator
    {
        return User::with(['roles', 'roles.permissions'])
            ->latest()
            ->paginate($perPage);
    }

    public function assignRoles(User $user, array $roleIds): void
    {
        $user->roles()->sync($roleIds);
        $this->permissionService->invalidateForUser($user->id);
    }

    public function assignPermissions(User $user, array $permissionIds): void
    {
        $user->permissions()->sync($permissionIds);
        $this->permissionService->invalidateForUser($user->id);
    }

    public function blockUser(User $user): void
    {
        $user->update(['status' => User::STATUS_BLOCKED]);
        $this->jwtService->invalidateUser($user->id);
    }

    public function unblockUser(User $user): void
    {
        $user->update(['status' => User::STATUS_ACTIVE]);
    }

    public function listRoles(int $perPage = 15): LengthAwarePaginator
    {
        return Role::with('permissions')->latest()->paginate($perPage);
    }

    public function listPermissions(int $perPage = 15): LengthAwarePaginator
    {
        return Permission::latest()->paginate($perPage);
    }

    public function createRole(string $roleName, ?string $description = null, bool $isSuperadmin = false): Role
    {
        return Role::create([
            'role_name' => $roleName,
            'description' => $description,
            'is_superadmin' => $isSuperadmin,
        ]);
    }

    public function assignPermissionsToRole(Role $role, array $permissionIds): void
    {
        $role->permissions()->sync($permissionIds);
        foreach ($role->users as $user) {
            $this->permissionService->invalidateForUser($user->id);
        }
    }

    public function createPermission(string $permissionName, ?string $description = null, bool $isAdminPermission = false): Permission
    {
        return Permission::create([
            'permission_name' => $permissionName,
            'description' => $description,
            'is_admin_permission' => $isAdminPermission,
        ]);
    }
}
