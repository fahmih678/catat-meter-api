<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PamController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MeterController;
use App\Http\Controllers\Api\MeterReadingController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\ReportController;

// V1 API Controllers
use App\Http\Controllers\Api\V1\AuthController as V1AuthController;
use App\Http\Controllers\Api\V1\MeterController as V1MeterController;
use App\Http\Controllers\Api\V1\CustomerController as V1CustomerController;
use App\Http\Controllers\Api\V1\PaymentController as V1PaymentController;
use App\Http\Controllers\Api\V1\DashboardController as V1DashboardController;
use App\Http\Controllers\Api\V1\ReportController as V1ReportController;
use App\Http\Controllers\Api\V1\CatatMeterController as V1CatatMeterController;
use App\Http\Controllers\Api\V1\MeterReadingController as V1MeterReadingController;

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
| API V1 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('v1.')->group(function () {

    // Authentication Routes (Public)
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/login', [V1AuthController::class, 'login'])->name('login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/profile', [V1AuthController::class, 'profile'])->name('profile');
            Route::put('/profile', [V1AuthController::class, 'updateProfile'])->name('update-profile');
            Route::post('/logout', [V1AuthController::class, 'logout'])->name('logout');
            // Route::post('/logout-all', [V1AuthController::class, 'logoutAll'])->name('logout-all');
            // Route::post('/refresh-token', [V1AuthController::class, 'refreshToken'])->name('refresh-token');
            // Route::get('/check-token', [V1AuthController::class, 'checkToken'])->name('check-token');
        });
    });

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        // Catat Meter Operations
        Route::get('/dashboard', [V1DashboardController::class, 'index'])->name('dashboard');

        Route::get('/month-list/{year}', [V1CatatMeterController::class, 'monthList'])->name('month-list');
        Route::get('/customer-list', [V1CustomerController::class, 'customerList'])->name('customer-list');
        Route::get('/unrecorded-customers', [V1CustomerController::class, 'unrecordedList'])->name('unrecorded-customers');
        Route::post('/create-month', [V1CatatMeterController::class, 'createMonth'])->name('create-month');

        Route::get('/meter-reading-list', [V1CatatMeterController::class, 'meterReadingList'])->name('meter-reading-list');

        // Meter Reading Operations
        Route::get('/customers/{id}/meter-input-data', [V1MeterReadingController::class, 'getMeterInputData'])->name('customer-meter-input-data');

        Route::post('/create-bill', [V1PaymentController::class, 'store'])->name('create-bill');
    });
});

/*
|--------------------------------------------------------------------------
| Legacy Authentication Routes (Public)
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
| Legacy Protected Routes (Require Authentication)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Legacy user route
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /*
    |--------------------------------------------------------------------------
    | SUPERADMIN ONLY ROUTES
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:superadmin'])->group(function () {

        // PAM MANAGEMENT (SuperAdmin Only)
        Route::prefix('pams')->name('pams.')->group(function () {
            Route::post('/', [PamController::class, 'store'])->name('store');
            Route::delete('/{id}', [PamController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [PamController::class, 'restore'])->name('restore');
        });

        // USER MANAGEMENT (SuperAdmin Only) - TODO: Create UserController
        Route::prefix('users')->name('users.')->group(function () {
            // Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
            // Route::post('/{id}/restore', [UserController::class, 'restore'])->name('restore');
            Route::delete('/{id}', function () {
                return response()->json(['message' => 'User deletion - SuperAdmin only']);
            })->name('destroy');
            Route::post('/{id}/restore', function () {
                return response()->json(['message' => 'User restoration - SuperAdmin only']);
            })->name('restore');
        });

        // SYSTEM MANAGEMENT (SuperAdmin Only)
        Route::prefix('system')->name('system.')->group(function () {
            Route::post('/backup', function () {
                return response()->json(['message' => 'Backup initiated']);
            })->name('backup');
            Route::get('/logs', function () {
                return response()->json(['message' => 'System logs']);
            })->name('logs');
            Route::post('/settings', function () {
                return response()->json(['message' => 'Settings updated']);
            })->name('settings');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | MANAGEMENT LEVEL ROUTES (SuperAdmin + Admin PAM)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:superadmin,admin,catat_meter', 'pam.scope'])->group(function () {

        // PAM MANAGEMENT (Read & Update)
        Route::prefix('pams')->name('pams.')->group(function () {
            Route::get('/', [PamController::class, 'index'])->name('index');
            Route::get('/{id}', [PamController::class, 'show'])->name('show');
            Route::put('/{id}', [PamController::class, 'update'])->name('update');
            Route::get('/{id}/statistics', [PamController::class, 'statistics'])->name('statistics');
            Route::post('/{id}/activate', [PamController::class, 'activate'])->name('activate');
            Route::post('/{id}/deactivate', [PamController::class, 'deactivate'])->name('deactivate');
        });

        // USER MANAGEMENT (CRUD except delete) - TODO: Create UserController
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', function () {
                return response()->json(['message' => 'User list - Management level']);
            })->name('index');
            Route::post('/', function () {
                return response()->json(['message' => 'User creation - Management level']);
            })->name('store');
            Route::get('/{id}', function ($id) {
                return response()->json(['message' => "User details: $id"]);
            })->name('show');
            Route::put('/{id}', function ($id) {
                return response()->json(['message' => "User update: $id"]);
            })->name('update');
            Route::post('/{id}/assign-role', function ($id) {
                return response()->json(['message' => "Role assignment: $id"]);
            })->name('assign-role');
        });

        // CUSTOMER MANAGEMENT (Full CRUD)
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/search', [CustomerController::class, 'search'])->name('search');
            Route::get('/pam/{pamId}', [CustomerController::class, 'byPam'])->name('by-pam');
            Route::get('/area/{areaId}', [CustomerController::class, 'byArea'])->name('by-area');
            Route::get('/pam/{pamId}/unpaid-bills', [CustomerController::class, 'unpaidBills'])->name('unpaid-bills');
            Route::get('/pam/{pamId}/without-meters', [CustomerController::class, 'withoutMeters'])->name('without-meters');
            Route::post('/{id}/activate', [CustomerController::class, 'activate'])->name('activate');
            Route::post('/{id}/deactivate', [CustomerController::class, 'deactivate'])->name('deactivate');
            Route::post('/{id}/restore', [CustomerController::class, 'restore'])->name('restore');
            Route::post('/{id}/transfer-area', [CustomerController::class, 'transferArea'])->name('transfer-area');
            Route::post('/{id}/change-tariff', [CustomerController::class, 'changeTariff'])->name('change-tariff');
            Route::apiResource('/', CustomerController::class)->parameter('', 'id');
        });

        // METER MANAGEMENT (Full CRUD) - Using existing controllers
        Route::prefix('meters')->name('meters.')->group(function () {
            Route::get('/search', [MeterController::class, 'search'])->name('search');
            Route::get('/customer/{customerId}', [MeterController::class, 'byCustomer'])->name('by-customer');
            Route::get('/area/{areaId}', [MeterController::class, 'byArea'])->name('by-area');
            Route::get('/{id}/statistics', [MeterController::class, 'statistics'])->name('statistics');
            Route::post('/{id}/activate', [MeterController::class, 'activate'])->name('activate');
            Route::post('/{id}/deactivate', [MeterController::class, 'deactivate'])->name('deactivate');
            Route::post('/{id}/restore', [MeterController::class, 'restore'])->name('restore');
            Route::apiResource('/', MeterController::class)->parameter('', 'id');
        });

        // METER RECORD MANAGEMENT (View & Approve) - Using existing controllers
        Route::prefix('meter-records')->name('meter-records.')->group(function () {
            Route::get('/', [MeterReadingController::class, 'index'])->name('index');
            Route::get('/{id}', [MeterReadingController::class, 'show'])->name('show');
            Route::put('/{id}', [MeterReadingController::class, 'update'])->name('update');
            Route::post('/{id}/approve', [MeterReadingController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [MeterReadingController::class, 'reject'])->name('reject');
            Route::get('/meter/{meterId}', [MeterReadingController::class, 'byMeter'])->name('by-meter');
            Route::get('/period/{period}', [MeterReadingController::class, 'byPeriod'])->name('by-period');
            Route::get('/meter/{meterId}/usage', [MeterReadingController::class, 'usage'])->name('usage');
            Route::get('/statistics', [MeterReadingController::class, 'statistics'])->name('statistics');
            Route::get('/missing-readings', [MeterReadingController::class, 'missingReadings'])->name('missing-readings');
        });

        // BILL MANAGEMENT (Full CRUD) - Using existing controllers
        Route::prefix('bills')->name('bills.')->group(function () {
            Route::get('/', [BillController::class, 'index'])->name('index');
            Route::post('/', [BillController::class, 'store'])->name('store');
            Route::get('/{id}', [BillController::class, 'show'])->name('show');
            Route::put('/{id}', [BillController::class, 'update'])->name('update');
            Route::delete('/{id}', [BillController::class, 'destroy'])->name('destroy');
            Route::get('/customer/{customerId}', [BillController::class, 'byCustomer'])->name('by-customer');
            Route::get('/pending', [BillController::class, 'pending'])->name('pending');
            Route::post('/{id}/pay', [BillController::class, 'markAsPaid'])->name('mark-paid');
            Route::post('/generate/{pamId}/{period}', [BillController::class, 'generateBills'])->name('generate');
        });

        // REPORTS (Full Access) - Using existing controllers
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/dashboard', [ReportController::class, 'dashboard'])->name('dashboard');
            Route::get('/monthly/{pamId}/{month}', [ReportController::class, 'monthly'])->name('monthly');
            Route::get('/volume-usage/{pamId}/{period}', [ReportController::class, 'volumeUsage'])->name('volume-usage');
            Route::get('/customer-statistics/{pamId}', [ReportController::class, 'customerStatistics'])->name('customer-statistics');
            Route::post('/generate-monthly/{pamId}/{month}', [ReportController::class, 'generateMonthly'])->name('generate-monthly');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | OPERATIONAL LEVEL ROUTES (SuperAdmin + Admin PAM + Catat Meter)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:superadmin,admin,catat_meter', 'pam.scope'])->group(function () {

        // CUSTOMER READ ACCESS
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/', [CustomerController::class, 'index'])->name('index');
            Route::get('/{id}', [CustomerController::class, 'show'])->name('show');
        });

        // METER READ ACCESS  
        Route::prefix('meters')->name('meters.')->group(function () {
            Route::get('/', [MeterController::class, 'index'])->name('index');
            Route::get('/{id}', [MeterController::class, 'show'])->name('show');
            Route::put('/{id}', [MeterController::class, 'update'])->name('update'); // For meter assignment
        });

        // METER RECORD OPERATIONS (Full CRUD except delete)
        Route::prefix('meter-records')->name('meter-records.')->group(function () {
            Route::post('/', [MeterReadingController::class, 'store'])->name('store');
            Route::post('/bulk-create', [MeterReadingController::class, 'bulkCreate'])->name('bulk-create');
        });

        // BASIC REPORTS
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/meter-readings', [ReportController::class, 'meterReadings'])->name('meter-readings');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | BILLING LEVEL ROUTES (SuperAdmin + Admin PAM + Pembayaran)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:superadmin,admin,pembayaran', 'pam.scope'])->group(function () {

        // CUSTOMER READ ACCESS (for billing info)
        Route::prefix('customers')->name('customers.billing.')->group(function () {
            Route::get('/billing-info', [CustomerController::class, 'billingInfo'])->name('billing-info');
        });

        // METER READ ACCESS (for billing calculation)
        Route::prefix('meters')->name('meters.billing.')->group(function () {
            Route::get('/for-billing', [MeterController::class, 'forBilling'])->name('for-billing');
        });

        // METER RECORD READ ACCESS (for billing)
        Route::prefix('meter-records')->name('meter-records.billing.')->group(function () {
            Route::get('/for-billing', [MeterReadingController::class, 'forBilling'])->name('for-billing');
        });

        // BILL READ AND PAYMENT ACCESS
        Route::prefix('bills')->name('bills.payment.')->group(function () {
            Route::get('/payment-pending', [BillController::class, 'paymentPending'])->name('payment-pending');
            Route::post('/{id}/mark-paid', [BillController::class, 'markPaid'])->name('mark-paid');
        });

        // PAYMENT REPORTS
        Route::prefix('reports')->name('reports.payment.')->group(function () {
            Route::get('/payment-summary', [ReportController::class, 'paymentSummary'])->name('payment-summary');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | COMMON ROUTES (All Authenticated Users)
    |--------------------------------------------------------------------------
    */

    // PAM INFO (Read-only for all)
    Route::prefix('pams')->name('pams.common.')->group(function () {
        Route::get('/active', [PamController::class, 'active'])->name('active');
        Route::get('/search', [PamController::class, 'search'])->name('search');
    });

    /*
    |--------------------------------------------------------------------------
    | SYSTEM HEALTH & INFO ROUTES (All Authenticated Users)
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
