<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Middleware\AuthenticateWithCookie;
use App\Http\Middleware\CorsMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['throttle:login', CorsMiddleware::class])
    ->name('login.submit');

Route::middleware([AuthenticateWithCookie::class, 'throttle:api'])->group(function () {
    Route::match(['get', 'post'], '/realtime/auth', function (Request $request) {
        return Broadcast::auth($request);
    })->name('broadcast.auth');

    Route::get('/session', [AuthController::class, 'session'])->name('api.session');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware([AuthenticateWithCookie::class, 'throttle:signaling'])->group(function () {
    Route::get('/call-state', [ChatController::class, 'callState'])->name('api.call.state');
    Route::post('/call-offer', [ChatController::class, 'createOffer'])->name('api.call.offer');
    Route::post('/call-answer', [ChatController::class, 'createAnswer'])->name('api.call.answer');
    Route::post('/call-candidate', [ChatController::class, 'addCandidate'])->name('api.call.candidate');
    Route::post('/call-end', [ChatController::class, 'endCall'])->name('api.call.end');
});
