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
        Route::get('/home', [ChatController::class, 'index'])->name('home');
        Route::post('/send-message', [ChatController::class, 'sendMessage'])->name('chat.send')->middleware('throttle:chat');
    });
});
