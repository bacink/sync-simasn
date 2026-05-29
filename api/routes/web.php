<?php

use App\Http\Controllers\SimAsn\ArchiveSyncController;
use App\Http\Controllers\SimAsnCallbackController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sim-asn/archive-sync', [ArchiveSyncController::class, 'index'])
    ->name('sim-asn.archive-sync.index');

// SIM-ASN OAuth
Route::get('/auth/sim-asn', [SimAsnCallbackController::class, 'initiate'])
    ->name('auth.sim-asn');
Route::get('/callback/sim-asn', [SimAsnCallbackController::class, 'callback'])
    ->name('callback.sim-asn');
