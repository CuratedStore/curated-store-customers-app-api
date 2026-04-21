<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

$notImplemented = function (string $feature) {
    return response()->json([
        'message' => 'Endpoint scaffolded but not implemented yet.',
        'feature' => $feature,
    ], 501);
};

Route::prefix('api')->group(function () {
    // Authentication routes
    Route::post('/auth/register', fn () => $notImplemented('auth.register'));
    Route::post('/auth/login', fn () => $notImplemented('auth.login'));
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', fn () => $notImplemented('auth.logout'));
        
        // Products
        Route::get('/products', fn () => $notImplemented('products.index'));
        Route::get('/products/{id}', fn () => $notImplemented('products.show'));
        
        // Categories
        Route::get('/categories', fn () => $notImplemented('categories.index'));
        
        // Cart
        Route::post('/cart/add', fn () => $notImplemented('cart.add'));
        Route::get('/cart', fn () => $notImplemented('cart.index'));
        Route::delete('/cart/{id}', fn () => $notImplemented('cart.remove'));
        
        // Orders
        Route::post('/orders', fn () => $notImplemented('orders.store'));
        Route::get('/orders', fn () => $notImplemented('orders.index'));
        Route::get('/orders/{id}', fn () => $notImplemented('orders.show'));
        
        // Account
        Route::get('/account/profile', fn () => $notImplemented('account.profile'));
        Route::put('/account/profile', fn () => $notImplemented('account.updateProfile'));
        Route::get('/account/addresses', fn () => $notImplemented('account.addresses'));
    });
});
