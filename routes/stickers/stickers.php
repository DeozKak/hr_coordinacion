<?php

use App\Http\Middleware\CheckPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StickersController;

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });


    Route::middleware( 'auth')->group(function () {
        Route::get('bitacora/stickers', [StickersController::class, 'index'])->name('bitacora.stickers')->middleware(CheckPermission::class . ':generar_bitacoras');
        Route::get('bitacora/stickers/getInventario', [StickersController::class, 'getInventario'])
            ->name('bitacora.stickers.getInventario')
            ->middleware(CheckPermission::class . ':generar_bitacoras');
        Route::post('bitacora/stickers/ActualizarInventario/{id}', [StickersController::class, 'ActualizarInventario'])->name('bitacora.stickers.ActualizarInventario')->middleware(CheckPermission::class . ':generar_bitacoras');
        Route::get('bitacora/stickers/ver/{id}', [StickersController::class, 'show'])->name('bitacora.stickers.show')->middleware(CheckPermission::class . ':generar_bitacoras');
        Route::post('bitacora/stickers/getData/{id}', [StickersController::class, 'getData'])->name('bitacora.stickers.getData')->middleware(CheckPermission::class . ':generar_bitacoras');
        Route::post('bitacora/stickers/update', [StickersController::class, 'update'])->name('bitacora.stickers.update')->middleware(CheckPermission::class . ':control_stickers');
        Route::post('bitacora/stickers/Asignar', [StickersController::class, 'asignar'])->name('bitacora.stickers.asignar')->middleware(CheckPermission::class . ':control_stickers');
        Route::get('stickers/getStickersAsignados/{idInspector}', [StickersController::class, 'getStickersAsignados'])
            ->name('bitacora.stickers.getStickersAsignados')->middleware(CheckPermission::class . ':control_stickers');
        Route::post('stickers/desasignar', [StickersController::class, 'desasignar'])
            ->name('bitacora.stickers.desasignar')->middleware(CheckPermission::class . ':control_stickers');


    });

});
