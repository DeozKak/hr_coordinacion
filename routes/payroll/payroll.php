<?php

use App\Http\Middleware\CheckPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NominaController;

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {
        Route::get('/nomina/reporteNomina', [NominaController::class, 'getReporteNomina'])->name('nomina.reporteNomina')->middleware(CheckPermission::class . ':gestion_nomina');
        Route::post('/nomina/generarReporteNomina', [NominaController::class, 'postReporteNomina'])->name('nomina.generarReporteNomina')->middleware(CheckPermission::class . ':gestion_nomina');
        Route::post('/nomina/guardarMultaRodamiento', [NominaController::class, 'guardarMultaRodamiento'])->name('nomina.guardarMultaRodamiento')->middleware(CheckPermission::class . ':gestion_nomina');
        Route::get('/nomina/parametrizarSalarioAux', [NominaController::class, 'parametrizarSalarioAux'])->name('nomina.parametrizarSalarioAux')->middleware(CheckPermission::class . ':gestion_nomina');
        Route::post('/nomina/guardarSalarioAux', [NominaController::class, 'guardarSalarioAux'])->name('nomina.guardarSalarioAux')->middleware(CheckPermission::class . ':gestion_nomina');
        Route::post('/nomina/actualizarSalarioAux', [NominaController::class, 'actualizarSalarioAux'])->name('nomina.actualizarSalarioAux')->middleware(CheckPermission::class . ':gestion_nomina');
    });
});
