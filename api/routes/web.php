<?php

use App\Http\Controllers\SimAsn\ArchiveSyncController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sim-asn/archive-sync', [ArchiveSyncController::class, 'index'])
    ->name('sim-asn.archive-sync.index');
