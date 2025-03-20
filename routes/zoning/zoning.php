<?php

use App\Http\Controllers\ZonificacionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckPermission;
Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {

        Route::get('/zonas', [ZonificacionController::class, 'index'])->name('zonas.index');
        Route::post('/zonas/store/Municipio', [ZonificacionController::class, 'storeMunicipio'])->name('zonas.storeMunicipio')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::get('/zonas/{id}/editMunicipio', [ZonificacionController::class, 'editMunicipio'])->name('zonas.editMunicipio')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::put('/zonas/{id}/updateMunicipio', [ZonificacionController::class, 'updateMunicipio'])->name('zonas.updateMunicipio')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::post('/zonas/changeStatusMunicipio', [ZonificacionController::class, 'changeStatusMunicipio'])->name('zonas.changeStatusMunicipio')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');

    });
});
