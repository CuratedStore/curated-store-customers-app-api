<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::prefix('api')->group(function () {
    // Authentication routes
    Route::post('/auth/register', 'Api\AuthController@register');
    Route::post('/auth/login', 'Api\AuthController@login');
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', 'Api\AuthController@logout');
        
        // Products
        Route::get('/products', 'Api\ProductController@index');
        Route::get('/products/{id}', 'Api\ProductController@show');
        
        // Categories
        Route::get('/categories', 'Api\CategoryController@index');
        
        // Cart
        Route::post('/cart/add', 'Api\CartController@add');
        Route::get('/cart', 'Api\CartController@index');
        Route::delete('/cart/{id}', 'Api\CartController@remove');
        
        // Orders
        Route::post('/orders', 'Api\OrderController@store');
        Route::get('/orders', 'Api\OrderController@index');
        Route::get('/orders/{id}', 'Api\OrderController@show');
        
        // Account
        Route::get('/account/profile', 'Api\AccountController@profile');
        Route::put('/account/profile', 'Api\AccountController@updateProfile');
        Route::get('/account/addresses', 'Api\AccountController@addresses');
    });
});
