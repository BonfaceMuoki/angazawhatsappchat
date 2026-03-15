<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasPermission
{
    public function __construct(private PermissionService $permissionService) {}

    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if ($this->permissionService->userIsSuperAdmin($user->id)) {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            if ($this->permissionService->userHasPermission($user->id, $permission)) {
                return $next($request);
            }
        }

        return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
    }
}
