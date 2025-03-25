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
        //CRUD MUNICIPIOS
        Route::post('/zonas/store/Municipio', [ZonificacionController::class, 'storeMunicipio'])->name('zonas.storeMunicipio')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::get('/zonas/{id}/editMunicipio', [ZonificacionController::class, 'editMunicipio'])->name('zonas.editMunicipio')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::put('/zonas/{id}/updateMunicipio', [ZonificacionController::class, 'updateMunicipio'])->name('zonas.updateMunicipio')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::post('/zonas/changeStatusMunicipio', [ZonificacionController::class, 'changeStatusMunicipio'])->name('zonas.changeStatusMunicipio')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        //CRUD BARRIOS
        Route::post('/zonas/store/Barrio', [ZonificacionController::class, 'storeBarrio'])->name('zonas.storeBarrio')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::get('/zonas/{id}/editBarrio', [ZonificacionController::class, 'editBarrio'])->name('zonas.editBarrio')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::put('/zonas/{id}/updateBarrio', [ZonificacionController::class, 'updateBarrio'])->name('zonas.updateBarrio')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        //CRUD GRUPOS
        Route::post('/zonas/store/Grupo', [ZonificacionController::class, 'storeGrupo'])->name('zonas.storeGrupo')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::get('/zonas/{id}/editGrupo', [ZonificacionController::class, 'editGrupo'])->name('zonas.editGrupo')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::put('/zonas/{id}/updateGrupo', [ZonificacionController::class, 'updateGrupo'])->name('zonas.updateGrupo')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        //CRUD SUB GRUPOS
        Route::post('/zonas/store/SubGrupo', [ZonificacionController::class, 'storeSubGrupo'])->name('zonas.storeSubGrupo')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::get('/zonas/{id}/editSubGrupo', [ZonificacionController::class, 'editSubGrupo'])->name('zonas.editSubGrupo')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::put('/zonas/{id}/updateSubGrupo', [ZonificacionController::class, 'updateSubGrupo'])->name('zonas.updateSubGrupo')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        //DATOS ASIGNADOR
        Route::get('/zonas/datosAsignador', [ZonificacionController::class, 'datosAsignador'])->name('zonas.datosAsignador')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');

    });
});
