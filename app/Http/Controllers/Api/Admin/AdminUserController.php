<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Jobs\SendVerificationEmailJob;
use App\Models\User;
use App\Services\AuthService;
use App\Services\InvitationService;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(
        private UserManagementService $userService,
        private InvitationService $invitationService,
        private AuthService $authService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);
        $perPage = (int) $request->get('per_page', 15);
        $users = $this->userService->listUsers($perPage);
        return ApiResponse::success($users, 'Users retrieved.');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);
        $valid = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);
        $user = $this->userService->createUser(
            $valid['name'],
            $valid['email'],
            $valid['password'],
            $valid['role_ids'] ?? []
        );
        $token = $this->authService->storeEmailVerificationToken($user);
        SendVerificationEmailJob::dispatch($user, $token);
        super_admin_audit('user.create', 'User', $user->id, null, $user->toArray());
        return ApiResponse::success($user->load('roles'), 'User created.', 201);
    }

    public function invite(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);
        $valid = $request->validate([
            'email' => 'required|email',
            'role_id' => 'required|exists:roles,id',
        ]);
        $invitation = $this->invitationService->invite(
            $valid['email'],
            (int) $valid['role_id'],
            $request->user()?->id
        );
        super_admin_audit('user.invite', 'UserInvitation', $invitation->id, null, $invitation->toArray());
        return ApiResponse::success($invitation->only(['id', 'email', 'role_id', 'expires_at']), 'Invitation sent.', 201);
    }

    public function assignRoles(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $this->authorize('assignRole', $user);
        $valid = $request->validate(['role_ids' => 'required|array', 'role_ids.*' => 'exists:roles,id']);
        $old = $user->roles->pluck('id')->toArray();
        $this->userService->assignRoles($user, $valid['role_ids']);
        super_admin_audit('user.assign_roles', 'User', $user->id, ['role_ids' => $old], ['role_ids' => $valid['role_ids']]);
        return ApiResponse::success(null, 'Roles assigned.');
    }

    public function assignPermissions(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $this->authorize('assignPermission', $user);
        $valid = $request->validate(['permission_ids' => 'required|array', 'permission_ids.*' => 'exists:permissions,id']);
        $old = $user->permissions->pluck('id')->toArray();
        $this->userService->assignPermissions($user, $valid['permission_ids']);
        super_admin_audit('user.assign_permissions', 'User', $user->id, ['permission_ids' => $old], ['permission_ids' => $valid['permission_ids']]);
        return ApiResponse::success(null, 'Permissions assigned.');
    }

    public function block(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $this->authorize('blockUser', $user);
        $this->userService->blockUser($user);
        super_admin_audit('user.block', 'User', $user->id, ['status' => 'active'], ['status' => 'blocked']);
        return ApiResponse::success(null, 'User blocked.');
    }
}
