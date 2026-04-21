<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::prefix('api')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/request-otp', [AuthController::class, 'requestOtp']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('api.token')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::get('/categories', [CategoryController::class, 'index']);

        Route::post('/cart/add', [CartController::class, 'add']);
        Route::get('/cart', [CartController::class, 'index']);
        Route::delete('/cart/{id}', [CartController::class, 'remove']);

        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
        Route::post('/orders/{id}/return', [OrderController::class, 'requestReturn']);
        Route::get('/orders/{id}/invoice', [OrderController::class, 'invoice']);

        Route::get('/account/profile', [AccountController::class, 'profile']);
        Route::put('/account/profile', [AccountController::class, 'updateProfile']);
        Route::get('/account/addresses', [AccountController::class, 'addresses']);
        Route::post('/account/addresses', [AccountController::class, 'addAddress']);
        Route::delete('/account/addresses/{id}', [AccountController::class, 'deleteAddress']);
        Route::put('/account/addresses/{id}/default', [AccountController::class, 'setDefaultAddress']);
        Route::get('/account/preferences', [AccountController::class, 'preferences']);
        Route::put('/account/preferences', [AccountController::class, 'updatePreferences']);
    });
});
