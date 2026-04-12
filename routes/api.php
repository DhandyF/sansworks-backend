<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
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
use App\Http\Controllers\API\UserController;

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

// Public Authentication Routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

// Authenticated Routes (require authentication, accessible by all roles)
Route::middleware(['auth:sanctum'])->group(function () {
    // Authentication Routes
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::get('/tokens', [AuthController::class, 'tokens']);
        Route::delete('/tokens/{tokenId}', [AuthController::class, 'revokeToken']);
    });

    // User info endpoint
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Dashboard Routes (all authenticated users)
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->middleware('throttle:60');
        Route::get('/trends', [DashboardController::class, 'trends'])->middleware('throttle:20');
    });

    // Master Data Routes (Read-only for all, Create/Update/Delete for Manager+)
    Route::prefix('sizes')->group(function () {
        Route::get('/', [SizeController::class, 'index']);
        Route::get('/{size}', [SizeController::class, 'show']);
    });

    Route::prefix('tailors')->group(function () {
        Route::get('/', [TailorController::class, 'index']);
        Route::get('/{tailor}', [TailorController::class, 'show']);
        Route::get('/{tailor}/statistics', [TailorController::class, 'statistics']);
    });

    Route::prefix('brands')->group(function () {
        Route::get('/', [BrandController::class, 'index']);
        Route::get('/{brand}', [BrandController::class, 'show']);
        Route::get('/{brand}/statistics', [BrandController::class, 'statistics']);
    });

    Route::prefix('articles')->group(function () {
        Route::get('/', [ArticleController::class, 'index']);
        Route::get('/{article}', [ArticleController::class, 'show']);
    });

    Route::prefix('fabrics')->group(function () {
        Route::get('/', [FabricController::class, 'index']);
        Route::get('/{fabric}', [FabricController::class, 'show']);
        Route::get('/inventory/summary', [FabricController::class, 'inventorySummary']);
    });

    // Production Flow Routes (Read-only for all, Create/Update/Delete for Manager+)
    Route::prefix('cutting-results')->group(function () {
        Route::get('/', [CuttingResultController::class, 'index']);
        Route::get('/{cuttingResult}', [CuttingResultController::class, 'show']);
        Route::get('/statistics', [CuttingResultController::class, 'statistics']);
    });

    Route::prefix('cutting-distributions')->group(function () {
        Route::get('/', [CuttingDistributionController::class, 'index']);
        Route::get('/{cuttingDistribution}', [CuttingDistributionController::class, 'show']);
        Route::get('/overdue', [CuttingDistributionController::class, 'overdue']);
        Route::get('/statistics', [CuttingDistributionController::class, 'statistics']);
    });

    Route::prefix('deposit-cutting-results')->group(function () {
        Route::get('/', [DepositCuttingResultController::class, 'index']);
        Route::get('/{depositCuttingResult}', [DepositCuttingResultController::class, 'show']);
        Route::get('/statistics', [DepositCuttingResultController::class, 'statistics']);
    });

    // Quality Control Flow Routes (Read-only for all, Create/Update/Delete for Manager+)
    Route::prefix('qc-results')->group(function () {
        Route::get('/', [QCResultController::class, 'index']);
        Route::get('/{qcResult}', [QCResultController::class, 'show']);
        Route::get('/statistics', [QCResultController::class, 'statistics']);
    });

    Route::prefix('repair-distributions')->group(function () {
        Route::get('/', [RepairDistributionController::class, 'index']);
        Route::get('/{repairDistribution}', [RepairDistributionController::class, 'show']);
        Route::get('/overdue', [RepairDistributionController::class, 'overdue']);
        Route::get('/statistics', [RepairDistributionController::class, 'statistics']);
    });

    Route::prefix('deposit-repair-results')->group(function () {
        Route::get('/', [DepositRepairResultController::class, 'index']);
        Route::get('/{depositRepairResult}', [DepositRepairResultController::class, 'show']);
        Route::get('/statistics', [DepositRepairResultController::class, 'statistics']);
    });

    // Statistics Routes (Read-only for all)
    Route::prefix('daily-statistics')->group(function () {
        Route::get('/', [DailyStatisticController::class, 'index'])->middleware('throttle:60');
        Route::get('/{dailyStatistic}', [DailyStatisticController::class, 'show'])->middleware('throttle:60');
        Route::get('/summary', [DailyStatisticController::class, 'summary'])->middleware('throttle:20');
        Route::get('/latest', [DailyStatisticController::class, 'latest'])->middleware('throttle:60');
    });

    // Activity Logs Routes (Read-only for all)
    Route::prefix('activity-logs')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index']);
        Route::get('/{activityLog}', [ActivityLogController::class, 'show']);
        Route::get('/summary', [ActivityLogController::class, 'summary']);
        Route::get('/recent', [ActivityLogController::class, 'recent']);
    });
});

// Manager+ Routes (require manager or admin role)
Route::middleware(['auth:sanctum', 'manager'])->group(function () {
    // Master Data - Create/Update/Delete
    Route::prefix('sizes')->group(function () {
        Route::post('/', [SizeController::class, 'store']);
        Route::put('/{size}', [SizeController::class, 'update']);
        Route::delete('/{size}', [SizeController::class, 'destroy']);
    });

    Route::prefix('tailors')->group(function () {
        Route::post('/', [TailorController::class, 'store']);
        Route::put('/{tailor}', [TailorController::class, 'update']);
        Route::delete('/{tailor}', [TailorController::class, 'destroy']);
    });

    Route::prefix('brands')->group(function () {
        Route::post('/', [BrandController::class, 'store']);
        Route::put('/{brand}', [BrandController::class, 'update']);
        Route::delete('/{brand}', [BrandController::class, 'destroy']);
    });

    Route::prefix('articles')->group(function () {
        Route::post('/', [ArticleController::class, 'store']);
        Route::put('/{article}', [ArticleController::class, 'update']);
        Route::delete('/{article}', [ArticleController::class, 'destroy']);
    });

    Route::prefix('fabrics')->group(function () {
        Route::post('/', [FabricController::class, 'store']);
        Route::put('/{fabric}', [FabricController::class, 'update']);
        Route::delete('/{fabric}', [FabricController::class, 'destroy']);
        Route::post('/{fabric}/adjust-quantity', [FabricController::class, 'adjustQuantity']);
    });

    // Production Flow - Create/Update/Delete
    Route::prefix('cutting-results')->group(function () {
        Route::post('/', [CuttingResultController::class, 'store']);
        Route::put('/{cuttingResult}', [CuttingResultController::class, 'update']);
        Route::delete('/{cuttingResult}', [CuttingResultController::class, 'destroy']);
    });

    Route::prefix('cutting-distributions')->group(function () {
        Route::post('/', [CuttingDistributionController::class, 'store']);
        Route::put('/{cuttingDistribution}', [CuttingDistributionController::class, 'update']);
        Route::delete('/{cuttingDistribution}', [CuttingDistributionController::class, 'destroy']);
    });

    Route::prefix('deposit-cutting-results')->group(function () {
        Route::post('/', [DepositCuttingResultController::class, 'store']);
        Route::put('/{depositCuttingResult}', [DepositCuttingResultController::class, 'update']);
        Route::delete('/{depositCuttingResult}', [DepositCuttingResultController::class, 'destroy']);
    });

    // Quality Control Flow - Create/Update/Delete
    Route::prefix('qc-results')->group(function () {
        Route::post('/', [QCResultController::class, 'store']);
        Route::put('/{qcResult}', [QCResultController::class, 'update']);
        Route::delete('/{qcResult}', [QCResultController::class, 'destroy']);
    });

    Route::prefix('repair-distributions')->group(function () {
        Route::post('/', [RepairDistributionController::class, 'store']);
        Route::put('/{repairDistribution}', [RepairDistributionController::class, 'update']);
        Route::delete('/{repairDistribution}', [RepairDistributionController::class, 'destroy']);
    });

    Route::prefix('deposit-repair-results')->group(function () {
        Route::post('/', [DepositRepairResultController::class, 'store']);
        Route::put('/{depositRepairResult}', [DepositRepairResultController::class, 'update']);
        Route::delete('/{depositRepairResult}', [DepositRepairResultController::class, 'destroy']);
    });
});

// Admin Only Routes (require admin role)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Statistics Management
    Route::prefix('daily-statistics')->group(function () {
        Route::post('/', [DailyStatisticController::class, 'store']);
        Route::put('/{dailyStatistic}', [DailyStatisticController::class, 'update']);
        Route::delete('/{dailyStatistic}', [DailyStatisticController::class, 'destroy']);
        Route::post('/{dailyStatistic}/recalculate', [DailyStatisticController::class, 'recalculate']);
    });

    // User Management
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::patch('/{user}/role', [UserController::class, 'updateRole']);
        Route::patch('/{user}/status', [UserController::class, 'toggleStatus']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
    });
});
