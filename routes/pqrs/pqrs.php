<?php

use App\Http\Middleware\CheckPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PQRSImportController;

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {

        Route::get('/pqrs',[PQRSImportController::class,'index'])->name('pqrs.index')->middleware(CheckPermission::class . ':ver_PQRS');
        Route::post('/pqrs/importar',[PQRSImportController::class,'import'])->name('pqrs.importar')->middleware(CheckPermission::class . ':ver_PQRS');
        Route::get('/pqrs/quejas', [PQRSImportController::class, 'getQuejas'])->name('pqrs.quejas');
    });
});
