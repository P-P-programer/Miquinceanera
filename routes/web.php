<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Middleware\AdminOnly;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'store'])->name('login.store');
    });

    Route::post('/logout', [AdminAuthController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    Route::get('/', [DashboardController::class, 'index'])
        ->middleware(['auth', AdminOnly::class])
        ->name('dashboard');
});
