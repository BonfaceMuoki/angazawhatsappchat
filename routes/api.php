<?php

use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/webhook/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'handle']);

Route::get('/conversations', [ConversationController::class, 'index']);
Route::post('/conversations/{phone}/read', [ConversationController::class, 'markRead']);
Route::get('/conversations/{phone}/messages', [ConversationController::class, 'messages']);
Route::post('/conversations/{phone}/messages', [ConversationController::class, 'sendMessage']);

Route::get('/push-vapid-public', [PushSubscriptionController::class, 'vapidPublic']);
Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store']);
Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy']);

Route::get('/', function (): JsonResponse {
    return response()->json([
        'name' => config('app.name'),
        'message' => 'API only. Use /api/* endpoints.',
        'docs' => '/api',
    ]);
});
