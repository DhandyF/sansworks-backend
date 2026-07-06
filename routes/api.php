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
use App\Http\Controllers\RepairController;
use App\Http\Controllers\RepairDepositController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\TailorController;
use App\Http\Controllers\TailorDetailController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    Route::get('users/trashed', [UserController::class, 'trashed']);
    Route::post('users/{id}/restore', [UserController::class, 'restore']);
    Route::apiResource('users', UserController::class);

    Route::get('brands/trashed', [BrandController::class, 'trashed']);
    Route::post('brands/{id}/restore', [BrandController::class, 'restore']);
    Route::apiResource('brands', BrandController::class);
    Route::get('brands/{id}/production-stats', [BrandDetailController::class, 'productionStats'])->middleware('brand.access');

    Route::get('tailors/trashed', [TailorController::class, 'trashed']);
    Route::post('tailors/{id}/restore', [TailorController::class, 'restore']);
    Route::apiResource('tailors', TailorController::class);
    Route::get('tailors/{id}/detail-stats', [TailorDetailController::class, 'detailStats']);

    Route::get('sizes/trashed', [SizeController::class, 'trashed']);
    Route::post('sizes/{id}/restore', [SizeController::class, 'restore']);
    Route::apiResource('sizes', SizeController::class);

    Route::get('articles/trashed', [ArticleController::class, 'trashed']);
    Route::post('articles/{id}/restore', [ArticleController::class, 'restore']);
    Route::apiResource('articles', ArticleController::class);

    Route::get('pre-orders/trashed', [PreOrderController::class, 'trashed']);
    Route::post('pre-orders/{id}/restore', [PreOrderController::class, 'restore']);
    Route::get('pre-orders/next-name', [PreOrderController::class, 'nextName']);
    Route::post('pre-orders/batch', [PreOrderController::class, 'storeBatch']);
    Route::get('pre-orders/{id}/detail-stats', [PreOrderDetailController::class, 'detailStats']);
    Route::apiResource('pre-orders', PreOrderController::class);

    Route::get('cutting-results/trashed', [CuttingResultController::class, 'trashed']);
    Route::post('cutting-results/{id}/restore', [CuttingResultController::class, 'restore']);
    Route::get('cutting-results/remaining', [CuttingResultController::class, 'remaining']);
    Route::apiResource('cutting-results', CuttingResultController::class);

    Route::get('cutting-distributions/trashed', [CuttingDistributionController::class, 'trashed']);
    Route::post('cutting-distributions/{id}/restore', [CuttingDistributionController::class, 'restore']);
    Route::get('cutting-distributions/remaining', [CuttingDistributionController::class, 'remaining']);
    Route::post('cutting-distributions/batch', [CuttingDistributionController::class, 'storeBatch']);
    Route::apiResource('cutting-distributions', CuttingDistributionController::class);

    Route::get('deposit-cutting-results/trashed', [DepositCuttingResultController::class, 'trashed']);
    Route::post('deposit-cutting-results/{id}/restore', [DepositCuttingResultController::class, 'restore']);
    Route::apiResource('deposit-cutting-results', DepositCuttingResultController::class);
    Route::post('deposit-cutting-results/batch', [DepositCuttingResultController::class, 'storeBatch']);

    Route::get('payslips/generate', [PayslipController::class, 'generate']);

    Route::get('shipments/trashed', [ShipmentController::class, 'trashed']);
    Route::post('shipments/{id}/restore', [ShipmentController::class, 'restore']);
    Route::get('shipments/remaining', [ShipmentController::class, 'remaining']);
    Route::apiResource('shipments', ShipmentController::class);

    Route::get('repairs/trashed', [RepairController::class, 'trashed']);
    Route::post('repairs/{id}/restore', [RepairController::class, 'restore']);
    Route::get('repairs/generate-name', [RepairController::class, 'generateName']);
    Route::get('repairs/available-articles', [RepairController::class, 'availableArticles']);
    Route::get('repairs/sewing-price', [RepairController::class, 'getSewingPrice']);
    Route::apiResource('repairs', RepairController::class);

    Route::get('repair-deposits/trashed', [RepairDepositController::class, 'trashed']);
    Route::post('repair-deposits/{id}/restore', [RepairDepositController::class, 'restore']);
    Route::get('repair-deposits/remaining', [RepairDepositController::class, 'remaining']);
    Route::apiResource('repair-deposits', RepairDepositController::class);
});
