<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\Web\UserManagementController;
use App\Http\Controllers\Web\Pam\{
    PamManagementController,
    AreaController,
    CustomerController,
    TariffController,
    FixedFeeController,
    TariffTierController
};

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/', function () {
        return redirect()->route('login');
    });
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth', 'role:superadmin')->group(function () {
    // Import routes - put first to avoid conflicts
    Route::prefix('import')->group(function () {
        Route::post('/sheets', [ImportController::class, 'getSheets']);
        Route::post('/process', [ImportController::class, 'import']);
        Route::get('/template', [ImportController::class, 'downloadTemplate']);
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
    Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
    Route::get('/import', [DashboardController::class, 'import'])->name('import');

    // PAM Management routes
    Route::prefix('pam')->name('pam.')->group(function () {
        Route::get('/', [PamManagementController::class, 'index'])->name('index');
        Route::get('/search', [PamManagementController::class, 'search'])->name('search');
        Route::get('/{id}', [PamManagementController::class, 'show'])->name('show');

        // Nested routes for PAM-specific management
        Route::prefix('{pamId}')->group(function () {
            // Customers within PAM
            Route::get('/customers', [CustomerController::class, 'index'])->name('customers');
            Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
            Route::get('/customers/form-data', [CustomerController::class, 'getFormData'])->name('customers.form-data');
            Route::get('/customers/{id}', [CustomerController::class, 'show'])->name('customers.show');
            Route::put('/customers/{id}', [CustomerController::class, 'update'])->name('customers.update');
            Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');

            // Generate unique numbers
            Route::get('/generate-customer-number', [CustomerController::class, 'generateCustomerNumber'])->name('customers.generate-number');
            Route::get('/generate-meter-number', [CustomerController::class, 'generateMeterNumber'])->name('customers.generate-meter-number');

            // Areas within PAM - using AreaController
            Route::get('/areas', [AreaController::class, 'index'])->name('areas');
            Route::post('/areas', [AreaController::class, 'store'])->name('areas.store')->middleware('role:superadmin');
            Route::get('/areas/{id}/edit', [AreaController::class, 'edit'])->name('areas.edit')->middleware('role:superadmin');
            Route::put('/areas/{id}', [AreaController::class, 'update'])->name('areas.update')->middleware('role:superadmin');
            Route::delete('/areas/{id}', [AreaController::class, 'destroy'])->name('areas.destroy')->middleware('role:superadmin');

            // Tariff Groups within PAM - using TariffController
            Route::get('/tariff-groups', [TariffController::class, 'groups'])->name('tariff-groups');
            Route::post('/tariff-groups', [TariffController::class, 'storeGroup'])->name('tariff-groups.store')->middleware('role:superadmin');
            Route::get('/tariff-groups/{id}/edit', [TariffController::class, 'editGroup'])->name('tariff-groups.edit')->middleware('role:superadmin');
            Route::put('/tariff-groups/{id}', [TariffController::class, 'updateGroup'])->name('tariff-groups.update')->middleware('role:superadmin');
            Route::delete('/tariff-groups/{id}', [TariffController::class, 'destroyGroup'])->name('tariff-groups.destroy')->middleware('role:superadmin');

            // Tariff Tiers within PAM - using TariffController
            Route::get('/tariff-tiers', [TariffController::class, 'tiers'])->name('tariff-tiers');
            Route::post('/tariff-tiers', [TariffController::class, 'storeTier'])->name('tariff-tiers.store')->middleware('role:superadmin');
            Route::put('/tariff-tiers/{id}', [TariffController::class, 'updateTier'])->name('tariff-tiers.update')->middleware('role:superadmin');
            Route::delete('/tariff-tiers/{id}', [TariffController::class, 'destroyTier'])->name('tariff-tiers.destroy')->middleware('role:superadmin');

            // Tariff Tiers within PAM - using TariffTierController
            Route::post('/tiers', [TariffTierController::class, 'store'])->name('tiers.store')->middleware('role:superadmin');
            Route::get('/tiers/{id}/edit', [TariffTierController::class, 'edit'])->name('tiers.edit')->middleware('role:superadmin');
            Route::put('/tiers/{id}', [TariffTierController::class, 'update'])->name('tiers.update')->middleware('role:superadmin');
            Route::delete('/tiers/{id}', [TariffTierController::class, 'destroy'])->name('tiers.destroy')->middleware('role:superadmin');

            // Fixed Fees within PAM - using FixedFeeController
            Route::get('/fixed-fees', [FixedFeeController::class, 'index'])->name('fixed-fees');
            Route::post('/fixed-fees', [FixedFeeController::class, 'store'])->name('fixed-fees.store')->middleware('role:superadmin');
            Route::get('/fixed-fees/{id}/edit', [FixedFeeController::class, 'edit'])->name('fixed-fees.edit')->middleware('role:superadmin');
            Route::put('/fixed-fees/{id}', [FixedFeeController::class, 'update'])->name('fixed-fees.update')->middleware('role:superadmin');
            Route::delete('/fixed-fees/{id}', [FixedFeeController::class, 'destroy'])->name('fixed-fees.destroy')->middleware('role:superadmin');
        });

        // PAM CRUD - Superadmin only for create, update, delete
        Route::post('/', [PamManagementController::class, 'store'])->name('store')->middleware('role:superadmin');
        Route::get('/{id}/edit', [PamManagementController::class, 'edit'])->name('edit')->middleware('role:superadmin');
        Route::put('/{id}', [PamManagementController::class, 'update'])->name('update')->middleware('role:superadmin');
        Route::delete('/{id}', [PamManagementController::class, 'destroy'])->name('destroy')->middleware('role:superadmin');
        Route::put('/{id}/toggle-status', [PamManagementController::class, 'toggleStatus'])->name('toggle-status')->middleware('role:superadmin');
    });

    // User Management routes
    Route::prefix('users')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('users');
        Route::get('/{id}', [UserManagementController::class, 'show'])->name('users.detail');
        Route::put('/{id}/update', [UserManagementController::class, 'update'])->name('users.update');
        Route::put('/{id}/password', [UserManagementController::class, 'updatePassword'])->name('users.password.update');
        Route::post('/{id}/role', [UserManagementController::class, 'updateRole'])->name('users.role.update');
        Route::post('/', [UserManagementController::class, 'store'])->name('users.store');
        Route::delete('/{id}', [UserManagementController::class, 'destroy'])->name('users.destroy');
        Route::post('/{id}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggle-status');
    });
});
