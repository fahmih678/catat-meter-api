<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PamController;
use App\Http\Controllers\Api\CustomerController;
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

/*
|--------------------------------------------------------------------------
| Authentication Routes (Public)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/refresh', [AuthController::class, 'refreshToken'])->name('refresh');
        Route::post('/revoke-all', [AuthController::class, 'revokeAllTokens'])->name('revoke-all');
    });
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Require Authentication)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Legacy user route
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /*
|--------------------------------------------------------------------------
| PAM Management Routes
|--------------------------------------------------------------------------
*/

    Route::prefix('pams')->name('pams.')->group(function () {
        // Additional PAM routes (MUST come before apiResource to avoid conflicts)
        Route::get('/active', [PamController::class, 'active'])->name('active');
        Route::get('/search', [PamController::class, 'search'])->name('search');

        // Standard CRUD routes
        Route::apiResource('/', PamController::class)->parameter('', 'id');

        // PAM statistics and management routes
        Route::get('/{id}/statistics', [PamController::class, 'statistics'])->name('statistics');

        // PAM status management
        Route::post('/{id}/activate', [PamController::class, 'activate'])->name('activate');
        Route::post('/{id}/deactivate', [PamController::class, 'deactivate'])->name('deactivate');
        Route::post('/{id}/restore', [PamController::class, 'restore'])->name('restore');
    });

    /*
|--------------------------------------------------------------------------
| Customer Management Routes
|--------------------------------------------------------------------------
*/

    Route::prefix('customers')->name('customers.')->group(function () {
        // Search route MUST come before apiResource to avoid conflicts
        Route::get('/search', [CustomerController::class, 'search'])->name('search');

        // Customer filtering routes
        Route::get('/pam/{pamId}', [CustomerController::class, 'byPam'])->name('by-pam');
        Route::get('/area/{areaId}', [CustomerController::class, 'byArea'])->name('by-area');

        // Customer analytics routes
        Route::get('/pam/{pamId}/unpaid-bills', [CustomerController::class, 'unpaidBills'])->name('unpaid-bills');
        Route::get('/pam/{pamId}/without-meters', [CustomerController::class, 'withoutMeters'])->name('without-meters');

        // Customer status management
        Route::post('/{id}/activate', [CustomerController::class, 'activate'])->name('activate');
        Route::post('/{id}/deactivate', [CustomerController::class, 'deactivate'])->name('deactivate');
        Route::post('/{id}/restore', [CustomerController::class, 'restore'])->name('restore');

        // Customer transfer and changes
        Route::post('/{id}/transfer-area', [CustomerController::class, 'transferArea'])->name('transfer-area');
        Route::post('/{id}/change-tariff', [CustomerController::class, 'changeTariff'])->name('change-tariff');

        // Standard CRUD routes
        Route::apiResource('/', CustomerController::class)->parameter('', 'id');
    });

    /*
|--------------------------------------------------------------------------
| Future Routes (Prepared for next modules)
|--------------------------------------------------------------------------
*/

    /*
|--------------------------------------------------------------------------
| Meter Management Routes
|--------------------------------------------------------------------------
*/

    Route::prefix('meters')->name('meters.')->group(function () {
        // Search route MUST come before apiResource to avoid conflicts
        Route::get('/search', [\App\Http\Controllers\Api\MeterController::class, 'search'])->name('search');

        // Meter filtering routes
        Route::get('/customer/{customerId}', [\App\Http\Controllers\Api\MeterController::class, 'byCustomer'])->name('by-customer');
        Route::get('/area/{areaId}', [\App\Http\Controllers\Api\MeterController::class, 'byArea'])->name('by-area');

        // Meter analytics routes
        Route::get('/{id}/statistics', [\App\Http\Controllers\Api\MeterController::class, 'statistics'])->name('statistics');

        // Meter status management
        Route::post('/{id}/activate', [\App\Http\Controllers\Api\MeterController::class, 'activate'])->name('activate');
        Route::post('/{id}/deactivate', [\App\Http\Controllers\Api\MeterController::class, 'deactivate'])->name('deactivate');
        Route::post('/{id}/restore', [\App\Http\Controllers\Api\MeterController::class, 'restore'])->name('restore');

        // Standard CRUD routes
        Route::apiResource('/', \App\Http\Controllers\Api\MeterController::class)->parameter('', 'id');
    });

    /*
|--------------------------------------------------------------------------
| Meter Record Management Routes
|--------------------------------------------------------------------------
*/

    Route::prefix('meter-records')->name('meter-records.')->group(function () {
        // Meter record filtering routes
        Route::get('/meter/{meterId}', [\App\Http\Controllers\Api\MeterRecordController::class, 'byMeter'])->name('by-meter');
        Route::get('/period/{period}', [\App\Http\Controllers\Api\MeterRecordController::class, 'byPeriod'])->name('by-period');

        // Meter record analytics routes
        Route::get('/meter/{meterId}/usage', [\App\Http\Controllers\Api\MeterRecordController::class, 'usage'])->name('usage');
        Route::get('/statistics', [\App\Http\Controllers\Api\MeterRecordController::class, 'statistics'])->name('statistics');
        Route::get('/missing-readings', [\App\Http\Controllers\Api\MeterRecordController::class, 'missingReadings'])->name('missing-readings');

        // Bulk operations
        Route::post('/bulk-create', [\App\Http\Controllers\Api\MeterRecordController::class, 'bulkCreate'])->name('bulk-create');

        // Standard CRUD routes
        Route::apiResource('/', \App\Http\Controllers\Api\MeterRecordController::class)->parameter('', 'id');
    });

    /*
|--------------------------------------------------------------------------
| Bill Management Routes (Placeholder Implementation)
|--------------------------------------------------------------------------
*/

    Route::prefix('bills')->name('bills.')->group(function () {
        // Bill filtering routes
        Route::get('/customer/{customerId}', [\App\Http\Controllers\Api\BillController::class, 'byCustomer'])->name('by-customer');
        Route::get('/pending', [\App\Http\Controllers\Api\BillController::class, 'pending'])->name('pending');

        // Bill operations
        Route::post('/{id}/pay', [\App\Http\Controllers\Api\BillController::class, 'markAsPaid'])->name('mark-paid');
        Route::post('/generate/{pamId}/{period}', [\App\Http\Controllers\Api\BillController::class, 'generateBills'])->name('generate');

        // Standard CRUD routes
        Route::apiResource('/', \App\Http\Controllers\Api\BillController::class)->parameter('', 'id');
    });

    /*
|--------------------------------------------------------------------------
| Report Routes (Placeholder Implementation)
|--------------------------------------------------------------------------
*/

    Route::prefix('reports')->name('reports.')->group(function () {
        // Dashboard and overview
        Route::get('/dashboard', [\App\Http\Controllers\Api\ReportController::class, 'dashboard'])->name('dashboard');

        // Report generation
        Route::get('/monthly/{pamId}/{month}', [\App\Http\Controllers\Api\ReportController::class, 'monthly'])->name('monthly');
        Route::get('/volume-usage/{pamId}/{period}', [\App\Http\Controllers\Api\ReportController::class, 'volumeUsage'])->name('volume-usage');
        Route::get('/customer-statistics/{pamId}', [\App\Http\Controllers\Api\ReportController::class, 'customerStatistics'])->name('customer-statistics');

        // Report generation endpoints
        Route::post('/generate-monthly/{pamId}/{month}', [\App\Http\Controllers\Api\ReportController::class, 'generateMonthly'])->name('generate-monthly');
    });

    // Meter Management Routes (placeholder)
    // Route::prefix('meters')->name('meters.')->group(function () {
    //     Route::apiResource('/', MeterController::class);
    //     Route::get('/customer/{customerId}', [MeterController::class, 'byCustomer']);
    //     Route::get('/pam/{pamId}/needing-reading', [MeterController::class, 'needingReading']);
    //     Route::get('/pam/{pamId}/not-recorded', [MeterController::class, 'notRecorded']);
    //     Route::post('/{id}/activate', [MeterController::class, 'activate']);
    //     Route::post('/{id}/deactivate', [MeterController::class, 'deactivate']);
    // });

    // Meter Record Management Routes (placeholder)
    // Route::prefix('meter-records')->name('meter-records.')->group(function () {
    //     Route::apiResource('/', MeterRecordController::class);
    //     Route::get('/pam/{pamId}/period/{period}', [MeterRecordController::class, 'byPamAndPeriod']);
    //     Route::get('/pam/{pamId}/pending', [MeterRecordController::class, 'pending']);
    //     Route::get('/pam/{pamId}/for-billing', [MeterRecordController::class, 'forBilling']);
    //     Route::post('/{id}/approve', [MeterRecordController::class, 'approve']);
    //     Route::post('/{id}/reject', [MeterRecordController::class, 'reject']);
    // });

    // Bill Management Routes (placeholder)
    // Route::prefix('bills')->name('bills.')->group(function () {
    //     Route::apiResource('/', BillController::class);
    //     Route::get('/customer/{customerId}', [BillController::class, 'byCustomer']);
    //     Route::get('/pam/{pamId}/pending', [BillController::class, 'pending']);
    //     Route::post('/{id}/pay', [BillController::class, 'markAsPaid']);
    //     Route::post('/generate/{pamId}/{period}', [BillController::class, 'generateBills']);
    // });

    // Report Routes (placeholder)
    // Route::prefix('reports')->name('reports.')->group(function () {
    //     Route::get('/monthly/{pamId}/{month}', [ReportController::class, 'monthly']);
    //     Route::get('/volume-usage/{pamId}/{period}', [ReportController::class, 'volumeUsage']);
    //     Route::get('/customer-statistics/{pamId}', [ReportController::class, 'customerStatistics']);
    //     Route::post('/generate-monthly/{pamId}/{month}', [ReportController::class, 'generateMonthly']);
    // });

    /*
|--------------------------------------------------------------------------
| System Routes
|--------------------------------------------------------------------------
*/

    // Health check route
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'services' => [
                'database' => 'connected',
                'cache' => 'available',
            ]
        ]);
    })->name('health');

    // API version info
    Route::get('/version', function () {
        return response()->json([
            'version' => '1.0.0',
            'api_version' => 'v1',
            'laravel_version' => app()->version(),
            'endpoints' => [
                'pams' => '/api/pams',
                'customers' => '/api/customers',
                'health' => '/api/health',
            ]
        ]);
    })->name('version');
}); // End of auth:sanctum middleware group
