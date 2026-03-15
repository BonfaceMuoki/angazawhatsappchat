<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRoleController extends Controller
{
    public function __construct(private UserManagementService $userService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);
        $perPage = (int) $request->get('per_page', 15);
        $roles = $this->userService->listRoles($perPage);
        return ApiResponse::success($roles, 'Roles retrieved.');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Role::class);
        $valid = $request->validate([
            'role_name' => 'required|string|max:255|unique:roles,role_name',
            'description' => 'nullable|string',
            'is_superadmin' => 'boolean',
        ]);
        $role = $this->userService->createRole(
            $valid['role_name'],
            $valid['description'] ?? null,
            (bool) ($valid['is_superadmin'] ?? false)
        );
        super_admin_audit('role.create', 'Role', $role->id, null, $role->toArray());
        return ApiResponse::success($role, 'Role created.', 201);
    }

    public function assignPermissions(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $this->authorize('assignPermissions', $role);
        $valid = $request->validate(['permission_ids' => 'required|array', 'permission_ids.*' => 'exists:permissions,id']);
        $old = $role->permissions->pluck('id')->toArray();
        $this->userService->assignPermissionsToRole($role, $valid['permission_ids']);
        super_admin_audit('role.assign_permissions', 'Role', $role->id, ['permission_ids' => $old], ['permission_ids' => $valid['permission_ids']]);
        return ApiResponse::success(null, 'Permissions assigned to role.');
    }
}
