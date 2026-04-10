<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\SizeController;
use App\Http\Controllers\API\TailorController;
use App\Http\Controllers\API\BrandController;
use App\Http\Controllers\API\ArticleController;
use App\Http\Controllers\API\FabricController;
use App\Http\Controllers\API\CuttingResultController;
use App\Http\Controllers\API\CuttingDistributionController;
use App\Http\Controllers\API\DepositCuttingResultController;
use App\Http\Controllers\API\QCResultController;
use App\Http\Controllers\API\RepairDistributionController;
use App\Http\Controllers\API\DepositRepairResultController;
use App\Http\Controllers\API\DailyStatisticController;
use App\Http\Controllers\API\ActivityLogController;
use App\Http\Controllers\API\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Dashboard Routes
Route::prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/trends', [DashboardController::class, 'trends']);
});

// Master Data Routes
Route::prefix('sizes')->group(function () {
    Route::get('/', [SizeController::class, 'index']);
    Route::post('/', [SizeController::class, 'store']);
    Route::get('/{size}', [SizeController::class, 'show']);
    Route::put('/{size}', [SizeController::class, 'update']);
    Route::delete('/{size}', [SizeController::class, 'destroy']);
});

Route::prefix('tailors')->group(function () {
    Route::get('/', [TailorController::class, 'index']);
    Route::post('/', [TailorController::class, 'store']);
    Route::get('/{tailor}', [TailorController::class, 'show']);
    Route::put('/{tailor}', [TailorController::class, 'update']);
    Route::delete('/{tailor}', [TailorController::class, 'destroy']);
    Route::get('/{tailor}/statistics', [TailorController::class, 'statistics']);
});

Route::prefix('brands')->group(function () {
    Route::get('/', [BrandController::class, 'index']);
    Route::post('/', [BrandController::class, 'store']);
    Route::get('/{brand}', [BrandController::class, 'show']);
    Route::put('/{brand}', [BrandController::class, 'update']);
    Route::delete('/{brand}', [BrandController::class, 'destroy']);
    Route::get('/{brand}/statistics', [BrandController::class, 'statistics']);
});

Route::prefix('articles')->group(function () {
    Route::get('/', [ArticleController::class, 'index']);
    Route::post('/', [ArticleController::class, 'store']);
    Route::get('/{article}', [ArticleController::class, 'show']);
    Route::put('/{article}', [ArticleController::class, 'update']);
    Route::delete('/{article}', [ArticleController::class, 'destroy']);
});

Route::prefix('fabrics')->group(function () {
    Route::get('/', [FabricController::class, 'index']);
    Route::post('/', [FabricController::class, 'store']);
    Route::get('/{fabric}', [FabricController::class, 'show']);
    Route::put('/{fabric}', [FabricController::class, 'update']);
    Route::delete('/{fabric}', [FabricController::class, 'destroy']);
    Route::post('/{fabric}/adjust-quantity', [FabricController::class, 'adjustQuantity']);
    Route::get('/inventory/summary', [FabricController::class, 'inventorySummary']);
});

// Production Flow Routes
Route::prefix('cutting-results')->group(function () {
    Route::get('/', [CuttingResultController::class, 'index']);
    Route::post('/', [CuttingResultController::class, 'store']);
    Route::get('/{cuttingResult}', [CuttingResultController::class, 'show']);
    Route::put('/{cuttingResult}', [CuttingResultController::class, 'update']);
    Route::delete('/{cuttingResult}', [CuttingResultController::class, 'destroy']);
    Route::get('/statistics', [CuttingResultController::class, 'statistics']);
});

Route::prefix('cutting-distributions')->group(function () {
    Route::get('/', [CuttingDistributionController::class, 'index']);
    Route::post('/', [CuttingDistributionController::class, 'store']);
    Route::get('/{cuttingDistribution}', [CuttingDistributionController::class, 'show']);
    Route::put('/{cuttingDistribution}', [CuttingDistributionController::class, 'update']);
    Route::delete('/{cuttingDistribution}', [CuttingDistributionController::class, 'destroy']);
    Route::get('/overdue', [CuttingDistributionController::class, 'overdue']);
    Route::get('/statistics', [CuttingDistributionController::class, 'statistics']);
});

Route::prefix('deposit-cutting-results')->group(function () {
    Route::get('/', [DepositCuttingResultController::class, 'index']);
    Route::post('/', [DepositCuttingResultController::class, 'store']);
    Route::get('/{depositCuttingResult}', [DepositCuttingResultController::class, 'show']);
    Route::put('/{depositCuttingResult}', [DepositCuttingResultController::class, 'update']);
    Route::delete('/{depositCuttingResult}', [DepositCuttingResultController::class, 'destroy']);
    Route::get('/statistics', [DepositCuttingResultController::class, 'statistics']);
});

// Quality Control Flow Routes
Route::prefix('qc-results')->group(function () {
    Route::get('/', [QCResultController::class, 'index']);
    Route::post('/', [QCResultController::class, 'store']);
    Route::get('/{qcResult}', [QCResultController::class, 'show']);
    Route::put('/{qcResult}', [QCResultController::class, 'update']);
    Route::delete('/{qcResult}', [QCResultController::class, 'destroy']);
    Route::get('/statistics', [QCResultController::class, 'statistics']);
});

Route::prefix('repair-distributions')->group(function () {
    Route::get('/', [RepairDistributionController::class, 'index']);
    Route::post('/', [RepairDistributionController::class, 'store']);
    Route::get('/{repairDistribution}', [RepairDistributionController::class, 'show']);
    Route::put('/{repairDistribution}', [RepairDistributionController::class, 'update']);
    Route::delete('/{repairDistribution}', [RepairDistributionController::class, 'destroy']);
    Route::get('/overdue', [RepairDistributionController::class, 'overdue']);
    Route::get('/statistics', [RepairDistributionController::class, 'statistics']);
});

Route::prefix('deposit-repair-results')->group(function () {
    Route::get('/', [DepositRepairResultController::class, 'index']);
    Route::post('/', [DepositRepairResultController::class, 'store']);
    Route::get('/{depositRepairResult}', [DepositRepairResultController::class, 'show']);
    Route::put('/{depositRepairResult}', [DepositRepairResultController::class, 'update']);
    Route::delete('/{depositRepairResult}', [DepositRepairResultController::class, 'destroy']);
    Route::get('/statistics', [DepositRepairResultController::class, 'statistics']);
});

// Statistics Routes
Route::prefix('daily-statistics')->group(function () {
    Route::get('/', [DailyStatisticController::class, 'index']);
    Route::post('/', [DailyStatisticController::class, 'store']);
    Route::get('/{dailyStatistic}', [DailyStatisticController::class, 'show']);
    Route::put('/{dailyStatistic}', [DailyStatisticController::class, 'update']);
    Route::delete('/{dailyStatistic}', [DailyStatisticController::class, 'destroy']);
    Route::post('/{dailyStatistic}/recalculate', [DailyStatisticController::class, 'recalculate']);
    Route::get('/summary', [DailyStatisticController::class, 'summary']);
    Route::get('/latest', [DailyStatisticController::class, 'latest']);
});

// Activity Logs Routes
Route::prefix('activity-logs')->group(function () {
    Route::get('/', [ActivityLogController::class, 'index']);
    Route::get('/{activityLog}', [ActivityLogController::class, 'show']);
    Route::get('/summary', [ActivityLogController::class, 'summary']);
    Route::get('/recent', [ActivityLogController::class, 'recent']);
});
