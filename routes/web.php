<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;

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
});
