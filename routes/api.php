<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\PreOrderController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\TailorController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    Route::apiResource('users', UserController::class);
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('tailors', TailorController::class);
    Route::apiResource('sizes', SizeController::class);
    Route::apiResource('articles', ArticleController::class);
    Route::get('pre-orders/next-name', [PreOrderController::class, 'nextName']);
    Route::post('pre-orders/batch', [PreOrderController::class, 'storeBatch']);
    Route::apiResource('pre-orders', PreOrderController::class);
});
