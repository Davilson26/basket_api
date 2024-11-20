<?php

use App\Http\Controllers\CartController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('cart')->group(function () {
    Route::post('/add', [CartController::class, 'addToCart']);
    Route::post('/update', [CartController::class, 'updateCart']);
    Route::post('/remove', [CartController::class, 'removeFromCart']);
    Route::get('/show', [CartController::class, 'showCart']);
    Route::post('/payment', [CartController::class, 'paymentCart']);

});
