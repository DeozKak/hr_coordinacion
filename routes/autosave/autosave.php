<?php
use App\Http\Middleware\CheckPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AutoGuardadoController;

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {
        Route::get('bitacora/restaurar/{id}', [AutoGuardadoController::class, 'Restaurar'])->name('bitacoras.restaurar')->middleware(CheckPermission::class . ':generar_bitacoras');
        Route::post('bitacora/borrar/{id}', [AutoGuardadoController::class, 'Borrar'])->name('bitacoras.borrar')->middleware(CheckPermission::class . ':generar_bitacoras');
        Route::post('bitacora/actualizar/{id}', [AutoGuardadoController::class, 'Actualizar'])->name('bitacoras.actualizar')->middleware(CheckPermission::class . ':generar_bitacoras');
        Route::post('bitacora/agregar', [AutoGuardadoController::class, 'Agregar'])->name('bitacoras.agregar')->middleware(CheckPermission::class . ':generar_bitacoras');
    });
});
