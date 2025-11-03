<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\Web\UserManagementController;
use App\Http\Controllers\Web\PamManagementController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/', function () {
        return redirect()->route('login');
    });
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
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
            Route::get('/customers', [PamManagementController::class, 'customers'])->name('customers');

            // Areas within PAM
            Route::get('/areas', [PamManagementController::class, 'areas'])->name('areas');
            Route::post('/areas', [PamManagementController::class, 'storeArea'])->name('areas.store')->middleware('role:superadmin');
            Route::put('/areas/{id}', [PamManagementController::class, 'updateArea'])->name('areas.update')->middleware('role:superadmin');
            Route::delete('/areas/{id}', [PamManagementController::class, 'destroyArea'])->name('areas.destroy')->middleware('role:superadmin');

            // Tariff Groups within PAM
            Route::get('/tariff-groups', [PamManagementController::class, 'tariffGroups'])->name('tariff-groups');
            Route::post('/tariff-groups', [PamManagementController::class, 'storeTariffGroup'])->name('tariff-groups.store')->middleware('role:superadmin');
            Route::put('/tariff-groups/{id}', [PamManagementController::class, 'updateTariffGroup'])->name('tariff-groups.update')->middleware('role:superadmin');
            Route::delete('/tariff-groups/{id}', [PamManagementController::class, 'destroyTariffGroup'])->name('tariff-groups.destroy')->middleware('role:superadmin');

            // Tariff Tiers within PAM
            Route::get('/tariff-tiers', [PamManagementController::class, 'tariffTiers'])->name('tariff-tiers');
            Route::post('/tariff-tiers', [PamManagementController::class, 'storeTariffTier'])->name('tariff-tiers.store')->middleware('role:superadmin');
            Route::put('/tariff-tiers/{id}', [PamManagementController::class, 'updateTariffTier'])->name('tariff-tiers.update')->middleware('role:superadmin');
            Route::delete('/tariff-tiers/{id}', [PamManagementController::class, 'destroyTariffTier'])->name('tariff-tiers.destroy')->middleware('role:superadmin');

            // Fixed Fees within PAM
            Route::get('/fixed-fees', [PamManagementController::class, 'fixedFees'])->name('fixed-fees');
            Route::post('/fixed-fees', [PamManagementController::class, 'storeFixedFee'])->name('fixed-fees.store')->middleware('role:superadmin');
            Route::put('/fixed-fees/{id}', [PamManagementController::class, 'updateFixedFee'])->name('fixed-fees.update')->middleware('role:superadmin');
            Route::delete('/fixed-fees/{id}', [PamManagementController::class, 'destroyFixedFee'])->name('fixed-fees.destroy')->middleware('role:superadmin');
        });

        // PAM CRUD - Superadmin only for create, update, delete
        Route::post('/', [PamManagementController::class, 'storePam'])->name('store')->middleware('role:superadmin');
        Route::put('/{id}', [PamManagementController::class, 'updatePam'])->name('update')->middleware('role:superadmin');
        Route::delete('/{id}', [PamManagementController::class, 'destroyPam'])->name('destroy')->middleware('role:superadmin');
    });

    // User Management routes
    Route::prefix('users')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('users');
        Route::get('/{id}', [UserManagementController::class, 'show'])->name('users.detail');
        Route::post('/{id}/update', [UserManagementController::class, 'update'])->name('users.update');
        Route::put('/{id}/password', [UserManagementController::class, 'updatePassword'])->name('users.password.update');
        Route::post('/{id}/role', [UserManagementController::class, 'updateRole'])->name('users.role.update');
        Route::post('/', [UserManagementController::class, 'store'])->name('users.store');
        Route::delete('/{id}', [UserManagementController::class, 'destroy'])->name('users.destroy');
        Route::post('/{id}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggle-status');
    });
});
