<?php

use App\Http\Middleware\CheckPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\FallidasController;

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {

        route::get('/produccion/fallidas', [FallidasController::class, 'index'])->name('produccion.fallidas')->middleware(CheckPermission::class . ':ver_residente');
        route::get('/produccion/fallidas/data', [FallidasController::class, 'getData'])->name('produccion.fallidas.data')->middleware(CheckPermission::class . ':ver_residente');
        Route::get('/produccion/detalles_fallidas/{fecha}/{inspector}', [FallidasController::class, 'detallesDiario'])->name('produccion.fallidas.detallesDiario')->middleware(CheckPermission::class . ':ver_residente,ver_produccion');
        Route::get('/obtener-url-detalles-fallidas', function (Request $request) {
            return route('produccion.fallidas.detallesDiario', [
                'fecha' => $request->fecha,
                'inspector' => $request->cc_inspector
            ]);
        })->name('obtener-url-detalles-fallidas');
        
    });
});