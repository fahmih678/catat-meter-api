<?php

// V1 API Controllers
use App\Http\Controllers\Api\V1\{
    AuthController as V1AuthController,
    ReportController as V1ReportController,
    CustomerController as V1CustomerController,
    PaymentController as V1PaymentController,
    MeterReadingController as V1MeterReadingController,
    UserController as V1UserController,
    PamController as V1PamController,
    RegisteredMonthController as V1RegisteredMonthController,
};

use Illuminate\Support\Facades\Route;

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
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('role:admin,catat_meter,loket', 'pam.scope')->group(function () {
            // Route for catat meter : month -> list meter reading -> customer -> input meter
            // month
            Route::get('/registered-months/list/{year}', [V1RegisteredMonthController::class, 'monthList'])->name('registered-month-year');
            Route::post('/registered-months/store', [V1RegisteredMonthController::class, 'store'])->name('registered-month-store');

            // list meter reading
            Route::get('/meter-readings/list', [V1MeterReadingController::class, 'meterReadingList'])->name('meter-reading-list');

            // Meter Reading Operations
            Route::get('/customers/unrecorded', [V1CustomerController::class, 'unrecordedList'])->name('customers-unrecorded');
            Route::get('/customers/{id}/meter-reading-form', [V1MeterReadingController::class, 'getMeterReadingForm'])->name('customers-meter-reading-form');
            Route::post('/meter-readings/store', [V1MeterReadingController::class, 'store'])->name('meter-reading-store');
            Route::post('/meter-readings/{meterReadingId}/destroy', [V1MeterReadingController::class, 'destroy'])->name('meter-reading-destroy');

            // Pay Operations
            Route::put('/meter-readings/{meterReadingId}/submit-to-pending', [V1MeterReadingController::class, 'submitToPending'])->name('meter-reading-pending');
            Route::get('/customers/{customerId}/bills', [V1PaymentController::class, 'getBills'])->name('get-bills');
            Route::post('/customers/{customerId}/bills/pay', [V1PaymentController::class, 'payBills'])->name('pay-bills');
            Route::delete('/bills/{billId}', [V1PaymentController::class, 'destroy'])->name('bills.destroy');

            // Bill Monthly Reports
            Route::get('/registered-months/available-months-report', [V1RegisteredMonthController::class, 'getAvailableMonthsReport'])->name('registered-month-list');
            Route::get('/reports/monthly-payment-report', [V1ReportController::class, 'monthlyPaymentReport'])->name('monthly-payment-report');
            Route::get('/reports/download-payment-report', [V1ReportController::class, 'downloadPaymentReport'])->name('download-payment-report');
        });

        Route::middleware('role:superadmin,admin')->group(function () {
            Route::get('/pams', [V1PamController::class, 'getPams'])->name('get-pams');
            // User Management
            Route::get('/users', [V1UserController::class, 'index'])->name('users.index');
            Route::get('/users/{id}', [V1UserController::class, 'show'])->name('users.show');
            Route::put('/users/{id}', [V1UserController::class, 'update'])->name('users.update');
            Route::post('/users/{id}/assign-role', [V1UserController::class, 'assignRole'])->name('users.assign-role');
            Route::delete('/users/{id}/remove-role', [V1UserController::class, 'removeRole'])->name('users.remove-role');
            Route::delete('/users/{id}', [V1UserController::class, 'destroy'])->name('users.destroy');

            // Sync Payment Summary Data
            Route::post('/sync/payment-summary/{registeredMonthId}', [V1ReportController::class, 'syncPaymentSummaryForMonth'])->name('sync.payment-summary.month');
        });

        Route::middleware('role:customer')->group(function () {
            // Get Bills for User
            Route::get('/me/bills', [V1CustomerController::class, 'getMyBills'])->name('customers.my-bills');
        });
    });
});
