<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\Web\UserManagementController;

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
