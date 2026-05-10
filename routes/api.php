<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\BrandDetailController;
use App\Http\Controllers\CuttingDistributionController;
use App\Http\Controllers\CuttingResultController;
use App\Http\Controllers\DepositCuttingResultController;
use App\Http\Controllers\PreOrderController;
use App\Http\Controllers\PreOrderDetailController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\TailorController;
use App\Http\Controllers\TailorDetailController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    Route::apiResource('users', UserController::class);
    Route::apiResource('brands', BrandController::class);
    Route::get('brands/{id}/production-stats', [BrandDetailController::class, 'productionStats']);
    Route::apiResource('tailors', TailorController::class);
    Route::get('tailors/{id}/detail-stats', [TailorDetailController::class, 'detailStats']);
    Route::apiResource('sizes', SizeController::class);
    Route::apiResource('articles', ArticleController::class);
    Route::get('pre-orders/next-name', [PreOrderController::class, 'nextName']);
    Route::post('pre-orders/batch', [PreOrderController::class, 'storeBatch']);
    Route::get('pre-orders/{id}/detail-stats', [PreOrderDetailController::class, 'detailStats']);
    Route::apiResource('pre-orders', PreOrderController::class);
    Route::get('cutting-results/remaining', [CuttingResultController::class, 'remaining']);
    Route::apiResource('cutting-results', CuttingResultController::class);
    Route::get('cutting-distributions/remaining', [CuttingDistributionController::class, 'remaining']);
    Route::post('cutting-distributions/batch', [CuttingDistributionController::class, 'storeBatch']);
    Route::apiResource('cutting-distributions', CuttingDistributionController::class);
    Route::apiResource('deposit-cutting-results', DepositCuttingResultController::class);
    Route::post('deposit-cutting-results/batch', [DepositCuttingResultController::class, 'storeBatch']);
    Route::get('payslips/generate', [PayslipController::class, 'generate']);
});
