<?php

use App\Http\Controllers\Api\Admin\AdminPermissionController;
use App\Http\Controllers\Api\Admin\AdminRoleController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\BotEdgeController;
use App\Http\Controllers\Api\Admin\BotFlowController;
use App\Http\Controllers\Api\Admin\BotNodeController;
use App\Http\Controllers\Api\Admin\BotSettingController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/webhook/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'handle']);

Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
Route::get('/leads', [LeadController::class, 'index']);
Route::get('/analytics/overview', [AnalyticsController::class, 'overview']);
Route::get('/conversations', [ConversationController::class, 'index']);
Route::post('/conversations/{phone}/read', [ConversationController::class, 'markRead']);
Route::get('/conversations/{phone}/messages', [ConversationController::class, 'messages']);
Route::post('/conversations/{phone}/messages', [ConversationController::class, 'sendMessage']);
Route::post('/conversations/{phone}/clear-messages', [ConversationController::class, 'clearMessages']);

Route::get('/push-vapid-public', [PushSubscriptionController::class, 'vapidPublic']);
Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store']);
Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy']);

// Auth (public)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/request-password-reset', [AuthController::class, 'requestPasswordReset']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/accept-invite', [AuthController::class, 'acceptInvite']);
});

// Admin (JWT required)
Route::prefix('admin')->middleware(['auth:api'])->group(function () {
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::post('/users', [AdminUserController::class, 'store']);
    Route::post('/users/invite', [AdminUserController::class, 'invite']);
    Route::post('/users/{id}/roles', [AdminUserController::class, 'assignRoles']);
    Route::post('/users/{id}/permissions', [AdminUserController::class, 'assignPermissions']);
    Route::post('/users/{id}/block', [AdminUserController::class, 'block']);

    Route::get('/roles', [AdminRoleController::class, 'index']);
    Route::post('/roles', [AdminRoleController::class, 'store']);
    Route::post('/roles/{id}/permissions', [AdminRoleController::class, 'assignPermissions']);

    Route::get('/permissions', [AdminPermissionController::class, 'index']);
    Route::post('/permissions', [AdminPermissionController::class, 'store']);

    Route::middleware(['permission:bot.manage,bot.flows,bot.nodes,bot.edges,bot.settings'])->group(function () {
        Route::get('/bot/flows', [BotFlowController::class, 'index']);
        Route::get('/bot/flows/{id}', [BotFlowController::class, 'show']);
        Route::post('/bot/flows', [BotFlowController::class, 'store']);
        Route::put('/bot/flows/{id}', [BotFlowController::class, 'update']);
        Route::delete('/bot/flows/{id}', [BotFlowController::class, 'destroy']);

        Route::get('/bot/nodes', [BotNodeController::class, 'index']);
        Route::post('/bot/nodes', [BotNodeController::class, 'store']);
        Route::put('/bot/nodes/{id}', [BotNodeController::class, 'update']);
        Route::delete('/bot/nodes/{id}', [BotNodeController::class, 'destroy']);

        Route::get('/bot/edges', [BotEdgeController::class, 'index']);
        Route::post('/bot/edges', [BotEdgeController::class, 'store']);
        Route::put('/bot/edges/{id}', [BotEdgeController::class, 'update']);
        Route::delete('/bot/edges/{id}', [BotEdgeController::class, 'destroy']);

        Route::get('/bot/settings', [BotSettingController::class, 'index']);
        Route::put('/bot/settings', [BotSettingController::class, 'update']);
    });
});

Route::get('/', function (): JsonResponse {
    return response()->json([
        'name' => config('app.name'),
        'message' => 'API only. Use /api/* endpoints.',
        'docs' => '/api',
    ]);
});
