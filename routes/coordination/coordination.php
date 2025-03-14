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
        Route::get('/gestion/filterData', [CoordinacionController::class, 'filterData'])->name('filterData')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        Route::post('/gestion/guardarProgramacionTecnico', [CoordinacionController::class, 'guardarProgramacionTecnico'])->name('guardarProgramacionTecnico')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        Route::get('/gestion/getGroupsForSede', [CoordinacionController::class, 'getGroupsForSede'])->name('getGroupsForSede')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        Route::get('/gestion/getDataSubGroups', [CoordinacionController::class, 'getDataSubGroups'])->name('getDataSubGroups')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        Route::get('/gestion/descargarExcelCoordinacion', [CoordinacionController::class, 'descargarExcelCoordinacion'])->name('descargarExcelCoordination')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        Route::post('/gestion/guardarCausaCierre', [CoordinacionController::class, 'guardarCausaCierre'])->name('guardarCausaCierre')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        Route::post('/gestion/guardarFechaSolicitudCierre', [CoordinacionController::class, 'guardarFechaSolicitudCierre'])->name('guardarFechaSolicitudCierre')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        // historico
        Route::get('/seguimiento/historico', [CoordinacionController::class, 'historico'])->name('historico')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        Route::get('/seguimiento/getDataHistorico', [CoordinacionController::class, 'getDataHistorico'])->name('getDataHistorico')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        Route::post('/gestion/marcaOrden', [CoordinacionController::class, 'marcaOrden'])->name('marcaOrden')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        Route::post('/gestion/marcaOrdenMasiva', [CoordinacionController::class, 'marcaOrdenMasiva'])->name('marcaOrdenMasiva')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        // Planilla
        Route::get('/gestion/planilla', [CoordinacionController::class, 'planilla'])->name('planilla')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        Route::get('/gestion/generarExcelPdf', [CoordinacionController::class, 'generarExcelPdf'])->name('generarExcelPdf')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        Route::post('/gestion/generarImpMasiva', [CoordinacionController::class, 'generarImpMasiva'])->name('generarImpMasiva')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        Route::post('/gestion/asignarOrdCercania', [CoordinacionController::class, 'asignarOrdCercania'])->name('asignarOrdCercania')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        // Movilidad
        Route::get('/gestion/aplicacion', [CoordinacionController::class, 'aplicacion'])->name('aplicacion')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        Route::get('/gestion/generarTablaAplication', [CoordinacionController::class, 'generarTablaAplication'])->name('generarTablaAplication')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
    });
});