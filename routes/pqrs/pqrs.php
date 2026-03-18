<?php

use App\Http\Controllers\PQRS\PQRSImportController;
use App\Http\Controllers\PQRS\CoordinacionPQRS;
use App\Http\Middleware\CheckPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {

        Route::get('/pqrs',[PQRSImportController::class,'index'])->name('pqrs.index')->middleware(CheckPermission::class . ':ver_PQRS');
        Route::post('/pqrs/importar',[PQRSImportController::class,'import'])->name('pqrs.importar')->middleware(CheckPermission::class . ':ver_PQRS');
        Route::get('/pqrs/quejas', [PQRSImportController::class, 'getQuejas'])->name('pqrs.quejas')->middleware(CheckPermission::class . ':ver_PQRS');
        Route::get('/pqrs/coordinacion', [CoordinacionPQRS::class, 'index'])->name('pqrs.coordinacion')->middleware(CheckPermission::class . ':ver_PQRS');
        Route::post('/pqrs/coordinacion/importarAsignadas', [CoordinacionPQRS::class, 'ImportOSF'])->name('pqrs.coordinacion.ImportOSF')->middleware(CheckPermission::class . ':ver_PQRS');
        Route::post('pqrs/coordinacion/update-asignado', [CoordinacionPQRS::class, 'updateAsignado'])->name('pqrs.coordinacion.updateAsignado')->middleware(CheckPermission::class . ':ver_PQRS');
        Route::get('pqrs/coordinacion/historico', [CoordinacionPQRS::class, 'getHistorico'])->name('pqrs.coordinacion.historico')->middleware(CheckPermission::class . ':ver_PQRS');
        Route::post('/pqrs/coordinacion/exportar-gdw', [CoordinacionPQRS::class, 'exportarGDW'])->name('pqrs.coordinacion.exportarGDW')->middleware(CheckPermission::class . ':ver_PQRS');
    });
});
