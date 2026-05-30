<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\AlbumPhotoController as AdminAlbumPhotoController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RegistrationController as AdminRegistrationController;
use App\Http\Controllers\Admin\SongRequestController as AdminSongRequestController;
use App\Http\Middleware\AdminOnly;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

Route::view('/album', 'welcome')->name('album');

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

    Route::post('/registrations/{registration}/cancel', [AdminRegistrationController::class, 'cancel'])
        ->middleware(['auth', AdminOnly::class])
        ->name('registrations.cancel');

    Route::delete('/album-photos/{albumPhoto}', [AdminAlbumPhotoController::class, 'destroy'])
        ->middleware(['auth', AdminOnly::class])
        ->name('album-photos.destroy');

    Route::get('/song-requests/export', [AdminSongRequestController::class, 'export'])
        ->middleware(['auth', AdminOnly::class])
        ->name('song-requests.export');
});
