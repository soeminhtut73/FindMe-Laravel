<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FriendController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\TokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    // ater add:
    // •	POST /auth/logout
    // •	POST /auth/refresh
    // •	POST /auth/email/verify etc.
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:api')->group(function () {
    // Friends
    Route::prefix('friends')->group(function () {
        Route::get('/', [FriendController::class, 'index']);        // list
        Route::get('/search', [FriendController::class, 'search']);       // q=...
        Route::post('/', [FriendController::class, 'store']);        // save/add friend
        Route::delete('/{id}', [FriendController::class, 'destroy']);      // delete
        Route::post('/{id}/block', [FriendController::class, 'block']);        // block
        Route::post('/{id}/unblock', [FriendController::class, 'unblock']);   // unblock
    });

    // Location sharing
    Route::prefix('location')->group(function () {
        Route::post('/send', [LocationController::class, 'send']);   // send encrypted
        Route::get('/{id}', [LocationController::class, 'show']);   // get by id
        // optional: list recent shares
        // Route::get('/',        [LocationController::class, 'index']);
    });

    // Tokens (balance & consumption)
    Route::prefix('tokens')->group(function () {
        Route::get('/', [TokenController::class, 'show']);      // balance
        Route::post('/topup', [TokenController::class, 'topup']);    // top-up
        // You can also consume automatically in LocationController@send
        // Route::post('/consume',  [TokenController::class, 'consume']);
    });

    // Current user
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
