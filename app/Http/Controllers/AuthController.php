<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function login(Request $request): JsonResponse
    {
        $key = 'login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return ApiResponse::error('Too many attempts. Try again later.', 429);
        }

        $valid = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $result = $this->authService->login($valid['email'], $valid['password']);
            return ApiResponse::success($result, $result['message']);
        } catch (ValidationException $e) {
            RateLimiter::hit($key);
            return ApiResponse::error($e->getMessage(), 422, $e->errors());
        }
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $valid = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        try {
            $result = $this->authService->verifyOtp($valid['email'], $valid['code']);
            return ApiResponse::success($result, $result['message']);
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 422, $e->errors());
        }
    }

    public function requestPasswordReset(Request $request): JsonResponse
    {
        $valid = $request->validate(['email' => 'required|email']);
        $result = $this->authService->requestPasswordReset($valid['email']);
        return ApiResponse::success($result, $result['message']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $valid = $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $result = $this->authService->resetPassword($valid['token'], $valid['password']);
            return ApiResponse::success($result, $result['message']);
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 422, $e->errors());
        }
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $valid = $request->validate(['token' => 'required|string']);
        try {
            $result = $this->authService->verifyEmail($valid['token']);
            return ApiResponse::success($result, $result['message']);
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 422, $e->errors());
        }
    }

    public function acceptInvite(Request $request): JsonResponse
    {
        $valid = $request->validate([
            'token' => 'required|string',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $result = $this->authService->acceptInvite($valid['token'], $valid['name'], $valid['password']);
            return ApiResponse::success($result, $result['message']);
        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), 422, $e->errors());
        }
    }
}
