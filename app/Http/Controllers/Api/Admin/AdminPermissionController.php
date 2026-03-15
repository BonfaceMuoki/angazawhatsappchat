<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPermissionController extends Controller
{
    public function __construct(private UserManagementService $userService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);
        $perPage = (int) $request->get('per_page', 15);
        $permissions = $this->userService->listPermissions($perPage);
        return ApiResponse::success($permissions, 'Permissions retrieved.');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Permission::class);
        $valid = $request->validate([
            'permission_name' => 'required|string|max:255|unique:permissions,permission_name',
            'description' => 'nullable|string',
            'is_admin_permission' => 'boolean',
        ]);
        $permission = $this->userService->createPermission(
            $valid['permission_name'],
            $valid['description'] ?? null,
            (bool) ($valid['is_admin_permission'] ?? false)
        );
        super_admin_audit('permission.create', 'Permission', $permission->id, null, $permission->toArray());
        return ApiResponse::success($permission, 'Permission created.', 201);
    }
}
