<?php

use App\Http\Middleware\CheckPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CorteProduccionController;

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {
        Route::get('/cortes_produccion', [CorteProduccionController::class, 'index'])->name('cortes_produccion.index')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::post('/cortes_produccion/store/Corte', [CorteProduccionController::class, 'storeCorte'])->name('cortes_produccion.store')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::post('/cortes_produccion/store/Municipio', [CorteProduccionController::class, 'storeMunicipio'])->name('cortes_produccion.storeMunicipio')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::post('/cortes_produccion/store/Sede', [CorteProduccionController::class, 'storeSede'])->name('cortes_produccion.storeSede')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::post('/cortes_produccion/store/Zona', [CorteProduccionController::class, 'storeZona'])->name('cortes_produccion.storeZona')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::post('/cortes_produccion/store/Causal', [CorteProduccionController::class, 'storeCausal'])->name('cortes_produccion.storeCausal')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::get('/cortes_producction/{id}/editCorte', [CorteProduccionController::class, 'editCorte'])->name('cortes_produccion.editCorte')->middleware(CheckPermission::class . ':ver_residente');
        Route::get('/cortes_producction/{id}/editCausal', [CorteProduccionController::class, 'editCausal'])->name('cortes_produccion.editCausal')->middleware(CheckPermission::class . ':ver_residente');
        Route::put('/cortes_produccion/{id}/updateCausal', [CorteProduccionController::class, 'updateCausal'])->name('cortes_produccion.updateCausal')->middleware(CheckPermission::class . ':ver_residente');
        Route::put('/cortes_produccion/{id}/updateCorte', [CorteProduccionController::class, 'updateCorte'])->name('cortes_produccion.updateCorte')->middleware(CheckPermission::class . ':ver_residente');
        Route::get('/cortes_producction/{id}/editMunicipio', [CorteProduccionController::class, 'editMunicipio'])->name('cortes_produccion.editMunicipio')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::put('/cortes_produccion/{id}/updateMunicipio', [CorteProduccionController::class, 'updateMunicipio'])->name('cortes_produccion.updateMunicipio')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::get('/cortes_producction/{id}/editSede', [CorteProduccionController::class, 'editSede'])->name('cortes_produccion.editSede')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::put('/cortes_produccion/{id}/updateSede', [CorteProduccionController::class, 'updateSede'])->name('cortes_produccion.updateSede')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::get('/cortes_producction/{id}/editZona', [CorteProduccionController::class, 'editZona'])->name('cortes_produccion.editZona')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::put('/cortes_produccion/{id}/updateZona', [CorteProduccionController::class, 'updateZona'])->name('cortes_produccion.updateZona')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::post('/cortes_produccion/changeStatusSede', [CorteProduccionController::class, 'changeStatusSede'])->name('cortes_produccion.changeStatusSede')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::post('/cortes_produccion/changeStatusZona', [CorteProduccionController::class, 'changeStatusZona'])->name('cortes_produccion.changeStatusZona')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::post('/cortes_produccion/changeStatusCausal', [CorteProduccionController::class, 'changeStatusCausal'])->name('cortes_produccion.changeStatusCausal')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::post('/cortes_produccion/changeStatusMunicipio', [CorteProduccionController::class, 'changeStatusMunicipio'])->name('cortes_produccion.changeStatusMunicipio')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
    });
});
