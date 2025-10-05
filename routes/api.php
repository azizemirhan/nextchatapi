<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\VisitorChatController;

Route::prefix('v1')->group(function () {
    // Public - Visitor chat endpoints

    Route::prefix('visitor/chat')->middleware('throttle:visitor')->group(function () {
        Route::post('/init', [VisitorChatController::class, 'initSession']);
        Route::post('/send', [VisitorChatController::class, 'sendMessage']);
        Route::get('/messages', [VisitorChatController::class, 'getMessages']);
        Route::post('/update-info', [VisitorChatController::class, 'updateVisitorInfo']);
    });


    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/user/fcm-token', [AuthController::class, 'updateFcmToken']);

        Route::prefix('chat')->group(function () {
            Route::get('/sessions', [ChatController::class, 'getSessions']);
            Route::get('/sessions/{sessionId}', [ChatController::class, 'getSession']);
            Route::post('/sessions/{sessionId}/send', [ChatController::class, 'sendMessage']);
            Route::delete('/messages/{messageId}', [ChatController::class, 'deleteMessage']);
            Route::delete('/sessions/{sessionId}', [ChatController::class, 'deleteSession']);
        });
    });
    // Login (no auth required)
    Route::post('/login', [AuthController::class, 'login']);
});
