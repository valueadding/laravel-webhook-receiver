<?php

use Illuminate\Support\Facades\Route;
use ValueAdding\WebhookReceiver\Http\Controllers\WebhookController;
use ValueAdding\WebhookReceiver\Http\Controllers\AuthController;
use ValueAdding\WebhookReceiver\Http\Controllers\LogViewerController;
use ValueAdding\WebhookReceiver\Http\Middleware\AuthenticateWebhook;
use ValueAdding\WebhookReceiver\Http\Middleware\AuthenticateViewer;

$prefix = config('webhook-receiver.route_prefix', 'webhook');

// Webhook endpoint for receiving logs (authenticated via bearer token)
Route::prefix('api/' . $prefix)->group(function () {
    Route::post('/logs', [WebhookController::class, 'store'])
        ->middleware(AuthenticateWebhook::class);
});

// Viewer authentication
Route::prefix('api/' . $prefix . '/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/check', [AuthController::class, 'check']);
});

// Viewer API endpoints (authenticated via session)
Route::prefix('api/' . $prefix . '/viewer')
    ->middleware(['web', AuthenticateViewer::class])
    ->group(function () {
        Route::get('/logs', [LogViewerController::class, 'index']);
        Route::get('/logs/{id}', [LogViewerController::class, 'show']);
        Route::get('/sources', [LogViewerController::class, 'sources']);
        Route::get('/levels', [LogViewerController::class, 'levels']);
        Route::get('/channels', [LogViewerController::class, 'channels']);
        Route::get('/stats', [LogViewerController::class, 'stats']);
    });
