<?php

use App\Http\Controllers\Api\AlbumPhotoController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\SongRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'miquinceanera-api',
    ]);
});

Route::post('/registrations', [RegistrationController::class, 'store']);
Route::get('/event/stats', [RegistrationController::class, 'stats'])->name('event.stats');
Route::get('/registrations/access/{accessCode}', [RegistrationController::class, 'showByAccessCode'])->name('registrations.access.show');
Route::get('/registrations/{qrCode}', [RegistrationController::class, 'show']);
Route::get('/registrations/{qrCode}/qr', [RegistrationController::class, 'qr'])->name('registrations.qr');
Route::get('/registrations/{qrCode}/qr/download', [RegistrationController::class, 'downloadQr'])->name('registrations.qr.download');
Route::post('/registrations/{qrCode}/scan', [RegistrationController::class, 'scan'])->name('registrations.scan');

Route::get('/album/photos', [AlbumPhotoController::class, 'index'])->name('album.photos.index');
Route::post('/album/photos', [AlbumPhotoController::class, 'store'])->name('album.photos.store');
Route::post('/song-requests', [SongRequestController::class, 'store'])->name('song-requests.store');
