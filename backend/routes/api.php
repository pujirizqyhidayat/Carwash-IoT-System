<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\MonitoringController;
use App\Http\Controllers\Api\V1\IoTController;
use App\Http\Controllers\Api\V1\SensorController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\VehicleEntryController;
use App\Http\Controllers\Api\V1\AuditLogController;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::prefix('iot')->group(function () {
        Route::post('vehicle-detections', [IoTController::class, 'vehicleDetections']);
        Route::post('sensors/heartbeat', [IoTController::class, 'heartbeat']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });

        Route::prefix('dashboard')->group(function () {
            Route::get('summary', [DashboardController::class, 'summary']);
            Route::get('recent-activities', [DashboardController::class, 'recentActivities']);
            Route::get('chart', [DashboardController::class, 'chart']);
        });

        Route::prefix('monitoring')->group(function () {
            Route::get('today', [MonitoringController::class, 'today']);
            Route::get('hourly', [MonitoringController::class, 'hourly']);
        });

        Route::apiResource('sensors', SensorController::class);
        Route::get('sensors/{id}/status', [SensorController::class, 'status']);

        Route::apiResource('users', UserController::class);
        Route::post('users/{id}/reset-password', [UserController::class, 'resetPassword']);

        Route::apiResource('locations', LocationController::class);

        Route::apiResource('vehicle-entries', VehicleEntryController::class)->only(['index', 'show', 'destroy']);

        Route::prefix('reports')->group(function () {
            Route::get('/', [ReportController::class, 'index']);
            Route::post('generate-daily', [ReportController::class, 'generateDaily']);
            Route::get('export/pdf', [ReportController::class, 'exportPdf']);
            Route::get('export/excel', [ReportController::class, 'exportExcel']);
        });

        Route::prefix('audit-logs')->group(function () {
            Route::get('/', [AuditLogController::class, 'index']);
            Route::get('{id}', [AuditLogController::class, 'show']);
            Route::get('export', [AuditLogController::class, 'export']);
        });
    });
});
