<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\KgbController;
use App\Http\Controllers\PmkController;
use App\Http\Controllers\RefGajiController;
use App\Http\Controllers\SimAsnController;
use Illuminate\Support\Facades\Route;

// Auth routes (no middleware)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // KGB Workflow Routes
    Route::prefix('kgb')->group(function () {
        Route::get('/', [KgbController::class, 'index']);
        Route::post('generate', [KgbController::class, 'generate']);
        Route::get('{kgb}', [KgbController::class, 'show']);
        Route::post('{kgb}/submit', [KgbController::class, 'submit']);
        Route::post('{kgb}/verify', [KgbController::class, 'verify']);
        Route::post('{kgb}/approve', [KgbController::class, 'approve']);
        Route::post('{kgb}/reject', [KgbController::class, 'reject']);
    });

    // PMK Routes
    Route::prefix('pmk')->group(function () {
        Route::get('/', [PmkController::class, 'index']);
        Route::post('/', [PmkController::class, 'store']);
        Route::get('{id}', [PmkController::class, 'show']);
        Route::delete('{id}', [PmkController::class, 'destroy']);
    });

    // SIM-ASN Proxy Routes
    Route::prefix('sim-asn')->group(function () {
        Route::get('pegawai', [SimAsnController::class, 'index']);
        Route::get('pegawai/{id}', [SimAsnController::class, 'show']);
    });

    // Ref Gaji Routes
    Route::prefix('ref-gaji')->group(function () {
        Route::get('/', [RefGajiController::class, 'index']);
        Route::get('{golongan}/{masaKerja}', [RefGajiController::class, 'show']);
    });
});
