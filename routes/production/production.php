<?php

use App\Http\Controllers\Produccion\ProduccionController;
use App\Http\Middleware\CheckPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {
        Route::get('/produccion/detalles_corte/{id}', [ProduccionController::class, 'detallesCorte'])->name('produccion.detallesCorte')->middleware(CheckPermission::class . ':ver_residente');
        Route::get('/produccion', [ProduccionController::class, 'index'])->name('produccion.index')->middleware(CheckPermission::class . ':ver_residente');
        Route::get('/produccion/detalles', [ProduccionController::class, 'detalles'])->name('produccion.detalles')->middleware(CheckPermission::class . ':ver_residente,ver_produccion');
        Route::get('/produccion/data', [ProduccionController::class, 'datosDetalles'])->name('produccion.datosDetalles')->middleware(CheckPermission::class . ':ver_residente,ver_produccion');
        Route::get('/produccion/detalles_diario/{fecha}/{inspector}', [ProduccionController::class, 'detallesDiario'])->name('produccion.detallesDiario')->middleware(CheckPermission::class . ':ver_residente,ver_produccion');
        Route::post('/produccion/detalles_diario/actualizar/{id}', [ProduccionController::class, 'ActualizarDetallesDiario'])->name('produccion.ActualizarDetallesDiario')->middleware(CheckPermission::class . ':ver_residente');
        Route::post('/produccion/detalles_diario/desasociar/{id}', [ProduccionController::class, 'eliminarDetallesDiario'])->name('produccion.eliminarDetallesDiario')->middleware(CheckPermission::class . ':ver_residente');
        Route::post('/produccion/detalles_diario/insertar', [ProduccionController::class, 'insertarContrato'])->name('produccion.insertarContrato')->middleware(CheckPermission::class . ':ver_residente');
        Route::get('/produccion/detalles_diario/bitacora/{fecha}/{ccOperario}', [ProduccionController::class, 'consultarBitacora'])->name('produccion.bitacora')->middleware(CheckPermission::class . ':ver_residente');
        Route::post('/produccion/detalles_diario/diseño_especial/{id}', [ProduccionController::class, 'diseñoEspecial'])->name('produccion.diseñoEspecial')->middleware(CheckPermission::class . ':ver_residente');
        Route::get('/obtener-url-detalles', function (Request $request) {
            return route('produccion.detallesDiario', [
                'fecha' => $request->fecha,
                'inspector' => $request->cc_inspector
            ]);
        })->name('obtener-url-detalles');
        Route::get('/obtener-url-bitacoras', function (Request $request) {
            return route('produccion.bitacora', [
                'fecha' => $request->fecha,
                'ccOperario' => $request->cc_inspector
            ]);
        })->name('obtener-url-bitacoras');
        Route::post('/produccion/getCorteData', [ProduccionController::class, 'getCorteData'])->name('produccion.getCorteData')->middleware(CheckPermission::class . ':ver_residente');
        Route::post('/crear-session-corte', [ProduccionController::class, 'crearSession'])->name('produccion.crearSession')->middleware(CheckPermission::class . ':ver_residente');
        Route::post('/produccion/guardar_no_dobles', [ProduccionController::class, 'guardarNoDobles'])->name('produccion.guardarNoDobles')->middleware(CheckPermission::class . ':ver_residente');
        Route::post('/produccion/contarDobles', [ProduccionController::class, 'contarDobles'])->name('produccion.contarDobles')->middleware(CheckPermission::class . ':ver_residente');
        Route::post('/produccion/storeNotDoublesHolidays', [ProduccionController::class, 'storeNotDoublesHolidays'])->name('produccion.storeNotDoublesHolidays')->middleware(CheckPermission::class . ':ver_residente');
        Route::post('/produccion/countDoublesHolidays', [ProduccionController::class, 'countDoublesHolidays'])->name('produccion.countDoublesHolidays')->middleware(CheckPermission::class . ':ver_residente');
        Route::post('/produccion/countDoublesSaturday', [ProduccionController::class, 'countDoublesSaturday'])->name('produccion.countDoublesSaturday')->middleware(CheckPermission::class . ':ver_residente');
        Route::post('/produccion/noContarDoblesSaturday', [ProduccionController::class, 'noContarDoblesSaturday'])->name('produccion.noContarDoblesSaturday')->middleware(CheckPermission::class . ':ver_residente');
    });
});
