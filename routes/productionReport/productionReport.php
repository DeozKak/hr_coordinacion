<?php

use App\Http\Middleware\CheckPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReporteProduccionController;

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {
        Route::get('/reporteProduccion', [ReporteProduccionController::class, 'diario'])->name('nomina.index')->middleware(CheckPermission::class . ':reporte_produccion');
        Route::get('/produccion/enero', [ReporteProduccionController::class, 'showEnero'])->name('produccion.enero');
        Route::get('/produccion/febrero', [ReporteProduccionController::class, 'showFebrero'])->name('produccion.febrero');
        Route::get('/produccion/marzo', [ReporteProduccionController::class, 'showMarzo'])->name('produccion.marzo');
        Route::get('/produccion/abril', [ReporteProduccionController::class, 'showAbril'])->name('produccion.abril');
        Route::get('/produccion/mayo', [ReporteProduccionController::class, 'showMayo'])->name('produccion.mayo');
        Route::get('/produccion/junio', [ReporteProduccionController::class, 'showJunio'])->name('produccion.junio');
        Route::get('/produccion/julio', [ReporteProduccionController::class, 'showJulio'])->name('produccion.julio');
        Route::get('/produccion/agosto', [ReporteProduccionController::class, 'showAgosto'])->name('produccion.agosto');
        Route::get('/produccion/septiembre', [ReporteProduccionController::class, 'showSeptiembre'])->name('produccion.septiembre');
        Route::get('/produccion/octubre', [ReporteProduccionController::class, 'showOctubre'])->name('produccion.octubre');
        Route::get('/produccion/noviembre', [ReporteProduccionController::class, 'showNoviembre'])->name('produccion.noviembre');
        Route::get('/produccion/diciembre', [ReporteProduccionController::class, 'showDiciembre'])->name('produccion.diciembre');
        Route::post('/produccion/guardar', [ReporteProduccionController::class, 'guardarProduccion'])->name('produccion.guardar');
        Route::post('/produccion/inspeccionIndustrial', [ReporteProduccionController::class, 'inspeccionIndustrial'])->name('produccion.guardarInspeccionIndustrial')->middleware(CheckPermission::class . ':reporte_produccion');
        Route::post('/produccion/metas', [ReporteProduccionController::class, 'insertarMetas'])->name('produccion.insertarMetas')->middleware(CheckPermission::class . ':reporte_produccion');
        Route::get('/produccion/ReporteConsolidado', [ReporteProduccionController::class, 'reporteConsolidado'])->name('produccion.reporteConsolidado')->middleware(CheckPermission::class . ':reporte_produccion');
        Route::post('/produccion/generarReportePorMes', [ReporteProduccionController::class, 'generarReportePorMes'])->name('produccion.generarReportePorMes')->middleware(CheckPermission::class . ':reporte_produccion');
        Route::post('/produccion/generarReporteConsolidado', [ReporteProduccionController::class, 'generarReporteConsolidado'])->name('nomina.generarReporteConsolidado')->middleware(CheckPermission::class . ':reporte_produccion');
        Route::get('/fechasParametros', [ReporteProduccionController::class, 'fechasProduccion'])->name('fechasProduccion.registrar')->middleware(CheckPermission::class . ':reporte_produccion');
        Route::post('/fechasParametro/guardar', [ReporteProduccionController::class, 'guardarFechasParametros'])->name('fechasParametro.guardar')->middleware(CheckPermission::class . ':reporte_produccion');
        Route::post('/fechasParametro/actualizar', [ReporteProduccionController::class, 'actualizarFechasParametros'])->name('fechasParametro.actualizar')->middleware(CheckPermission::class . ':reporte_produccion');
    });
});
