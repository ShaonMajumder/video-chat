<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Middleware\AuthenticateWithCookie;
use App\Http\Middleware\EncryptCookies;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:global'])->group(function () {
    Route::view('/', 'welcome')->name('landing');
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');

    Route::middleware([EncryptCookies::class, AuthenticateWithCookie::class])->group(function () {
        Route::redirect('/home', '/app')->name('home');
        Route::get('/app', [ChatController::class, 'dashboard'])->name('app.dashboard');
        Route::get('/app/chat', [ChatController::class, 'chatHub'])->name('app.chat');
        Route::get('/app/chat/{user}', [ChatController::class, 'conversation'])->name('app.chat.show');
        Route::get('/app/profile', [ChatController::class, 'profile'])->name('app.profile');
        Route::get('/app/settings', [ChatController::class, 'settings'])->name('app.settings');
        Route::post('/send-message', [ChatController::class, 'sendMessage'])->name('chat.send')->middleware('throttle:chat');
    });
});
