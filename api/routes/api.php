<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\KgbController;
use App\Http\Controllers\Api\V1\PmkController;
use App\Http\Controllers\Api\V1\Ref\GajiController;
use App\Http\Controllers\Api\V1\Ref\OpdController;
use App\Http\Controllers\Api\V1\SimAsn\PegawaiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes — KGB System
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register-from-sim-asn', [AuthController::class, 'registerFromSimAsn'])
        ->middleware('guest:sanctum');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // KGB
        Route::get('/kgb', [KgbController::class, 'index']);
        Route::post('/kgb/generate', [KgbController::class, 'store']);
        Route::get('/kgb/{id}', [KgbController::class, 'show']);
        Route::put('/kgb/{id}', [KgbController::class, 'update']);
        Route::delete('/kgb/{id}', [KgbController::class, 'destroy']);
        Route::post('/kgb/{id}/submit', [KgbController::class, 'submit']);
        Route::post('/kgb/{id}/verify', [KgbController::class, 'verify']);
        Route::post('/kgb/{id}/approve', [KgbController::class, 'approve']);
        Route::post('/kgb/{id}/reject', [KgbController::class, 'reject']);
        Route::get('/kgb/{id}/snapshot', [KgbController::class, 'snapshot']);
        Route::get('/kgb/{id}/document', [KgbController::class, 'document']);

        // PMK
        Route::get('/pmk', [PmkController::class, 'index']);
        Route::post('/pmk', [PmkController::class, 'store']);
        Route::get('/pmk/{id}', [PmkController::class, 'show']);
        Route::put('/pmk/{id}', [PmkController::class, 'update']);
        Route::delete('/pmk/{id}', [PmkController::class, 'destroy']);

        // SIM-ASN Pegawai
        Route::get('/sim-asn/pegawai', [PegawaiController::class, 'index']);
        Route::get('/sim-asn/pegawai/{id}', [PegawaiController::class, 'show']);
        Route::get('/sim-asn/pegawai/{id}/golongan', [PegawaiController::class, 'golongan']);
        Route::get('/sim-asn/pegawai/{id}/jabatan', [PegawaiController::class, 'jabatan']);

        // Referensi Gaji
        Route::get('/ref/gaji', [GajiController::class, 'index']);
        Route::post('/ref/gaji', [GajiController::class, 'store']);
        Route::get('/ref/gaji/{id}', [GajiController::class, 'show']);
        Route::put('/ref/gaji/{id}', [GajiController::class, 'update']);
        Route::delete('/ref/gaji/{id}', [GajiController::class, 'destroy']);
        Route::get('/ref/opd', [OpdController::class, 'index']);

        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/dashboard/pending', [DashboardController::class, 'pending']);
        Route::get('/dashboard/upcoming', [DashboardController::class, 'upcoming']);
    });
});
