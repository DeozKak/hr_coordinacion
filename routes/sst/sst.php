<?php

use App\Http\Controllers\SstController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckPermission;
Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {

        Route::get('/preoperacional', [SstController::class, 'index'])->name('sst.index')->middleware(CheckPermission::class . ':gestion_preoperacional');
        Route::post('/preoperacional/exportar', [SstController::class, 'ExportarPreoperacional'])->name('sst.exportar')->middleware(CheckPermission::class . ':gestion_preoperacional');
    });
});
