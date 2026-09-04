<?php

use App\Http\Middleware\CheckPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Programacion\ProgramacionAgendamientoController;
use App\Http\Controllers\Programacion\ProgramacionController;
use App\Http\Controllers\Programacion\ProgramacionImportacionController;

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {
        Route::get('/programacion', [ProgramacionController::class, 'index'])->name('programacion.index')->middleware(CheckPermission::class . ':generar_programacion');
        Route::get('/programacion/create', [ProgramacionController::class, 'create'])->name('programacion.create')->middleware(CheckPermission::class . ':generar_programacion');
        Route::post('/programacion/base', [ProgramacionImportacionController::class, 'base'])->name('programacion.base')->middleware(CheckPermission::class . ':generar_programacion');
        Route::get('/programacion/busqueda/{contrato}', [ProgramacionController::class, 'busqueda'])->name('programacion.busqueda')->middleware(CheckPermission::class . ':generar_programacion');
        Route::post('/programacion/store', [ProgramacionController::class, 'store'])->name('programacion.store')->middleware(CheckPermission::class . ':generar_programacion');
        Route::delete('/programacion/delete', [ProgramacionController::class, 'destroy'])->name('programacion.destroy')->middleware(CheckPermission::class . ':generar_programacion');
        Route::get('/programacion/show/{id}', [ProgramacionController::class, 'show'])->name('programacion.show')->middleware(CheckPermission::class . ':generar_programacion');
        Route::put('/programacion/update/{id}', [ProgramacionController::class, 'update'])->name('programacion.update')->middleware(CheckPermission::class . ':generar_programacion');
        Route::delete('/programacion/erase/{id}', [ProgramacionController::class, 'erase'])->name('programacion.erase')->middleware(CheckPermission::class . ':generar_programacion');
        Route::post('/programacion/finish/{id}', [ProgramacionController::class, 'finish'])->name('programacion.finish')->middleware(CheckPermission::class . ':generar_programacion');
        Route::get('/programacion/detalles', [ProgramacionAgendamientoController::class, 'detalles'])->name('programacion.detalles')->middleware(CheckPermission::class . ':ver_programacion');
        Route::post('programacion/agendamiento', [ProgramacionAgendamientoController::class, 'agendamiento'])->name('programacion.agendamiento')->middleware(CheckPermission::class . ':ver_programacion');
        Route::post('programacion/masivos', [ProgramacionImportacionController::class, 'masivos'])->name('programacion.masivos')->middleware(CheckPermission::class . ':ver_programacion');
        Route::post('programacion/exportar', [ProgramacionAgendamientoController::class, 'exportar'])->name('programacion.exportar')->middleware(CheckPermission::class . ':ver_programacion');
        Route::get('programacion/buscar_por_contrato', [ProgramacionController::class, 'buscarPorContrato'])->name('programacion.buscar_por_contrato')->middleware(CheckPermission::class . ':ver_programacion');
        Route::post('programacion/plantilla/store', [ProgramacionController::class, 'PlantillaStore'])->name('programacion.PlantillaStore')->middleware(CheckPermission::class . ':ver_programacion');
        Route::post('programacion/callCenterGDO', [ProgramacionImportacionController::class, 'callCenterGDO'])->name('programacion.callCenterGDO')->middleware(CheckPermission::class . ':ver_programacion');
        Route::post('programacion/exportarSup', [ProgramacionAgendamientoController::class, 'exportarSup'])->name('programacion.exportarSup')->middleware(CheckPermission::class . ':ver_programacion');
        Route::get('programacion/reAsignar/{fecha}',[ProgramacionAgendamientoController::class, 'ReAsignarProgramacion'])->name('programacion.reAsignar')->middleware(CheckPermission::class . ':ver_programacion');
    });
});
