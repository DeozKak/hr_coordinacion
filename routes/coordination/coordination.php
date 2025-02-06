<?php

use App\Http\Middleware\CheckPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CoordinacionController;

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {
        Route::get('/gestion/coordinacion', [CoordinacionController::class, 'coordinacion'])->name('coordinacion')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        Route::get('/gestion/getdataCoordinacionRP', [CoordinacionController::class, 'getdataCoordinacionRP'])->name('getdataCoordinacionRP')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        Route::post('/gestion/filterData', [CoordinacionController::class, 'filterData'])->name('filterData')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
    });
});
