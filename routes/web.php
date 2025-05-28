<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth.cookie', 'throttle:global'])->group(function () {

    Route::get('/', function () {
        return view('welcome');
    });
    
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    
    Route::middleware(['auth.cookie'])->group(function () {
        Route::get('/', [ChatController::class, 'index']);
        Route::post('/send-message', [ChatController::class, 'sendMessage'])->name('chat.send')->middleware('throttle:chat');
    });

});
