<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\VisitorChatController;

Route::prefix('v1')->group(function () {
    // Public - Visitor chat endpoints
    Route::post('/visitor/chat/init', [VisitorChatController::class, 'initSession']);

    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/user/fcm-token', [AuthController::class, 'updateFcmToken']);

        // Visitor chat (protected)
        Route::post('/visitor/chat/send', [VisitorChatController::class, 'sendMessage']);
        Route::get('/visitor/chat/messages', [VisitorChatController::class, 'getMessages']);
        Route::post('/visitor/chat/update-info', [VisitorChatController::class, 'updateVisitorInfo']);

        // Admin chat
        Route::prefix('chat')->group(function () {
            Route::get('/sessions', [ChatController::class, 'getSessions']);
            Route::get('/sessions/{sessionId}', [ChatController::class, 'getSession']);
            Route::post('/sessions/{sessionId}/send', [ChatController::class, 'sendMessage']);
            Route::delete('/messages/{messageId}', [ChatController::class, 'deleteMessage']);
        });
    });

    // Login (no auth required)
    Route::post('/login', [AuthController::class, 'login']);
});
