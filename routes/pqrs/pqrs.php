<?php

use App\Http\Controllers\PQRS\PQRSImportController;
use App\Http\Controllers\PQRS\CoordinacionPQRS;
use App\Http\Middleware\CheckPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PQRS\CoordinacionEstadisticas;

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {


        Route::get('/pqrs/coordinacion', [CoordinacionPQRS::class, 'index'])->name('pqrs.coordinacion')->middleware(CheckPermission::class . ':ver_PQRS');
        Route::post('/pqrs/coordinacion/importarAsignadas', [CoordinacionPQRS::class, 'ImportOSF'])->name('pqrs.coordinacion.ImportOSF')->middleware(CheckPermission::class . ':coordinacion_pqrs');
        Route::post('pqrs/coordinacion/update-asignado', [CoordinacionPQRS::class, 'updateAsignado'])->name('pqrs.coordinacion.updateAsignado')->middleware(CheckPermission::class . ':coordinacion_pqrs,ver_PQRS');
        Route::get('pqrs/coordinacion/historico', [CoordinacionPQRS::class, 'getHistorico'])->name('pqrs.coordinacion.historico')->middleware(CheckPermission::class . ':coordinacion_pqrs,:ver_PQRS');
        Route::post('/pqrs/coordinacion/exportar-gdw', [CoordinacionPQRS::class, 'exportarGDW'])->name('pqrs.coordinacion.exportarGDW')->middleware(CheckPermission::class . ':coordinacion_pqrs');
        Route::get('/pqrs/coordinacion/datos-actualizados', [App\Http\Controllers\PQRS\CoordinacionPQRS::class, 'getDatosActualizados'])->name('pqrs.coordinacion.datosActualizados')->middleware(CheckPermission::class . ':ver_PQRS');
        Route::get('/pqrs/coordinacion/estadisticas', [CoordinacionEstadisticas::class, 'index'])->name('pqrs.coordinacion.estadisticas')->middleware(CheckPermission::class . ':ver_PQRS');
        Route::post('/pqrs/coordinacion/exportar-supervisores', [CoordinacionPQRS::class, 'exportarSupervisorExcel'])->name('pqrs.coordinacion.exportarSupervisores')->middleware(CheckPermission::class . ':ver_PQRS');
        Route::get('/pqrs/coordinacion/getSupervisores', [CoordinacionPQRS::class, 'getSupervisores'])->name('pqrs.coordinacion.getSupervisores')->middleware(CheckPermission::class . ':ver_PQRS');
    });
});
