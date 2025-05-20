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
        Route::post('/zonas/changeStatusTable', [ZonificacionController::class, 'changeStatusTable'])->name('zonas.changeStatusTable')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
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
        //RUTA BUSCADOR
        Route::get('/zonas/buscador', [ZonificacionController::class, 'buscador'])->name('zonas.buscador')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::post('/zonas/asignarBarrio',[ZonificacionController::class, 'asignarBarrio'])->name('zonas.asignarBarrio')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        //RUTA ASIGNADOR
        Route::post('/zonas/asignar', [ZonificacionController::class, 'asignar'])->name('zonas.asignar')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        //RUTA PARA ACTUALIZAR SELECTS BUSCADOR
        Route::get('/zonas/selects', [ZonificacionController::class, 'UpdateSelects'])->name('zonas.actualizarSelects')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        //RUTAS PARA ASIGNAR ZONAS A INSPECTORES
        Route::get('/zonas/responsables-form', [ZonificacionController::class, 'responsablesForm'])->name('zonas.responsablesForm')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::get('/zonas/{id}/responsablesInsp', [ZonificacionController::class, 'responsablesInsp'])->name('zonas.responsablesInsp')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::get('/zonas/inspectores-por-grupo', [ZonificacionController::class, 'inspectoresPorGrupo'])->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');;
        Route::post('/zonas/{id_sub}/{id_grup}/responsables-store', [ZonificacionController::class, 'responsablesStore'])->name('zonas.responsablesStore')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        //RUTAS PARA INSERCIÓN MASIVA
        Route::post('/zonas/insercionMasiva',[ZonificacionController::class, 'recepcionMasiva'])->name('zonas.recepcionMasiva')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
    });
});
