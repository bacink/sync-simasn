<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes — KGB System
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/auth/login', [App\Http\Controllers\Api\V1\AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/auth/logout', [App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
        Route::get('/auth/me', [App\Http\Controllers\Api\V1\AuthController::class, 'me']);

        // KGB
        Route::get('/kgb', [App\Http\Controllers\Api\V1\KgbController::class, 'index']);
        Route::post('/kgb/generate', [App\Http\Controllers\Api\V1\KgbController::class, 'store']);
        Route::get('/kgb/{id}', [App\Http\Controllers\Api\V1\KgbController::class, 'show']);
        Route::put('/kgb/{id}', [App\Http\Controllers\Api\V1\KgbController::class, 'update']);
        Route::delete('/kgb/{id}', [App\Http\Controllers\Api\V1\KgbController::class, 'destroy']);
        Route::post('/kgb/{id}/submit', [App\Http\Controllers\Api\V1\KgbController::class, 'submit']);
        Route::post('/kgb/{id}/verify', [App\Http\Controllers\Api\V1\KgbController::class, 'verify']);
        Route::post('/kgb/{id}/approve', [App\Http\Controllers\Api\V1\KgbController::class, 'approve']);
        Route::post('/kgb/{id}/reject', [App\Http\Controllers\Api\V1\KgbController::class, 'reject']);
        Route::get('/kgb/{id}/snapshot', [App\Http\Controllers\Api\V1\KgbController::class, 'snapshot']);
        Route::get('/kgb/{id}/document', [App\Http\Controllers\Api\V1\KgbController::class, 'document']);

        // PMK
        Route::get('/pmk', [App\Http\Controllers\Api\V1\PmkController::class, 'index']);
        Route::post('/pmk', [App\Http\Controllers\Api\V1\PmkController::class, 'store']);
        Route::get('/pmk/{id}', [App\Http\Controllers\Api\V1\PmkController::class, 'show']);
        Route::put('/pmk/{id}', [App\Http\Controllers\Api\V1\PmkController::class, 'update']);
        Route::delete('/pmk/{id}', [App\Http\Controllers\Api\V1\PmkController::class, 'destroy']);

        // SIM-ASN Pegawai
        Route::get('/sim-asn/pegawai', [App\Http\Controllers\Api\V1\SimAsn\PegawaiController::class, 'index']);
        Route::get('/sim-asn/pegawai/{id}', [App\Http\Controllers\Api\V1\SimAsn\PegawaiController::class, 'show']);
        Route::get('/sim-asn/pegawai/{id}/golongan', [App\Http\Controllers\Api\V1\SimAsn\PegawaiController::class, 'golongan']);
        Route::get('/sim-asn/pegawai/{id}/jabatan', [App\Http\Controllers\Api\V1\SimAsn\PegawaiController::class, 'jabatan']);

        // Referensi Gaji
        Route::get('/ref/gaji', [App\Http\Controllers\Api\V1\Ref\GajiController::class, 'index']);
        Route::post('/ref/gaji', [App\Http\Controllers\Api\V1\Ref\GajiController::class, 'store']);
        Route::get('/ref/gaji/{id}', [App\Http\Controllers\Api\V1\Ref\GajiController::class, 'show']);
        Route::put('/ref/gaji/{id}', [App\Http\Controllers\Api\V1\Ref\GajiController::class, 'update']);
        Route::delete('/ref/gaji/{id}', [App\Http\Controllers\Api\V1\Ref\GajiController::class, 'destroy']);

        // Dashboard
        Route::get('/dashboard/stats', [App\Http\Controllers\Api\V1\DashboardController::class, 'stats']);
        Route::get('/dashboard/pending', [App\Http\Controllers\Api\V1\DashboardController::class, 'pending']);
        Route::get('/dashboard/upcoming', [App\Http\Controllers\Api\V1\DashboardController::class, 'upcoming']);
    });
});